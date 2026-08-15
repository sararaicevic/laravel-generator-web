<?php

namespace App\Services\Generation;

class LaravelProjectGenerator
{
    public function generate(array $specification, string $outputDir): void
    {
        $this->ensureCleanDirectory($outputDir);

        $appName = $specification['app'];

        $this->write($outputDir.'/README.md', $this->readme($appName, $specification['entities']));
        $this->write($outputDir.'/routes/web.php', $this->routes($specification['entities']));
        $this->write($outputDir.'/resources/views/layouts/app.blade.php', $this->layout($appName, $specification['entities']));

        $migrationEntities = $this->sortEntitiesForMigrations($specification['entities']);

        foreach ($specification['entities'] as $entity) {
            $this->write($outputDir.'/app/Models/'.$entity['name'].'.php', $this->model($entity));
            $this->write($outputDir.'/app/Http/Controllers/'.$entity['name'].'Controller.php', $this->controller($entity));
            $this->writeViews($outputDir, $entity);
        }

        foreach ($migrationEntities as $index => $entity) {
            $this->write(
                $outputDir.'/database/migrations/'.$this->migrationFileName($index, $entity).'.php',
                $this->migration($entity),
            );
        }
    }

    private function ensureCleanDirectory(string $dir): void
    {
        if (is_dir($dir)) {
            $it = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST,
            );

            foreach ($it as $file) {
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }
        }

        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
    }

    private function write(string $path, string $content): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents($path, $content);
    }

    private function readme(string $appName, array $entities): string
    {
        $list = collect($entities)
            ->map(function (array $entity) {
                $relations = collect($entity['relations'] ?? [])
                    ->map(fn (array $relation) => $relation['type'].' '.$relation['target'])
                    ->implode(', ');

                return '- '.$entity['name'].' (`'.$entity['table'].'`)'.($relations ? ' - relacije: '.$relations : '');
            })
            ->implode("\n");

        return <<<MD
# {$appName}

Ovaj Laravel kod je generisan na osnovu DSL specifikacije.

## Generisani entiteti

{$list}

## Sadržaj

- Eloquent modeli
- Resource kontroleri
- Migracije
- Web rute
- Blade prikazi za CRUD operacije

MD;
    }

    private function routes(array $entities): string
    {
        $firstRoute = $entities[0]['route'];

        $resourceRoutes = collect($entities)
            ->map(fn (array $entity) => "Route::resource('{$entity['route']}', {$entity['name']}Controller::class);")
            ->implode("\n");

        $controllers = collect($entities)
            ->map(fn (array $entity) => "use App\\Http\\Controllers\\{$entity['name']}Controller;")
            ->implode("\n");

        return <<<PHP
<?php

use Illuminate\Support\Facades\Route;
{$controllers}

Route::get('/', function () {
    return redirect()->route('{$firstRoute}.index');
});

{$resourceRoutes}

PHP;
    }

    private function model(array $entity): string
    {
        $fillable = collect($this->fillableAttributes($entity))
            ->map(fn (string $attribute) => "        '{$attribute}',")
            ->implode("\n");

        $relations = collect($entity['relations'] ?? [])
            ->map(fn (array $relation) => $this->modelRelationMethod($relation))
            ->filter()
            ->implode("\n");

        $displayField = collect($entity['fields'])
            ->first(fn (array $field) => in_array($field['type'], ['string', 'email', 'text'], true))['name'] ?? null;
        $displayExpression = $displayField
            ? "\$this->{$displayField} ?: (string) \$this->id"
            : "(string) \$this->id";

        $relationsBlock = $relations ? "\n{$relations}\n" : '';

        return <<<PHP
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class {$entity['name']} extends Model
{
    protected \$fillable = [
{$fillable}
    ];
{$relationsBlock}
    public function displayName(): string
    {
        return {$displayExpression};
    }
}

PHP;
    }

    private function fillableAttributes(array $entity): array
    {
        $foreignKeys = collect($this->belongsToRelations($entity))
            ->map(fn (array $relation) => $relation['foreign_key'])
            ->all();

        return array_merge(
            collect($entity['fields'])->pluck('name')->all(),
            $foreignKeys,
        );
    }

    private function modelRelationMethod(array $relation): string
    {
        $target = $relation['target'];
        $method = $relation['method'];

        if ($relation['type'] === 'belongsTo') {
            return <<<PHP

    public function {$method}()
    {
        return \$this->belongsTo({$target}::class);
    }
PHP;
        }

        if ($relation['type'] === 'hasMany') {
            return <<<PHP

    public function {$method}()
    {
        return \$this->hasMany({$target}::class);
    }
PHP;
        }

        return '';
    }

    private function controller(array $entity): string
    {
        $rules = collect($entity['fields'])
            ->map(function (array $field) use ($entity) {
                $rule = $field['required'] ? 'required' : 'nullable';
                $rule .= '|'.$this->validationRule($field['type']);
                if ($field['unique']) {
                    $rule .= '|unique:'.$entity['table'].','.$field['name'];
                }

                return "            '{$field['name']}' => '{$rule}',";
            })
            ->merge(collect($this->belongsToRelations($entity))
                ->map(fn (array $relation) => "            '{$relation['foreign_key']}' => 'required|exists:{$relation['target_table']},id',"))
            ->implode("\n");

        $route = $entity['route'];
        $variable = $entity['variable'];
        $collection = $entity['collection'];
        $with = $this->belongsToRelations($entity) === []
            ? ''
            : "->with(['".collect($this->belongsToRelations($entity))->pluck('method')->implode("', '")."'])";
        $relationImports = collect($this->belongsToRelations($entity))
            ->pluck('target')
            ->unique()
            ->reject(fn (string $target) => $target === $entity['name'])
            ->map(fn (string $target) => "use App\\Models\\{$target};")
            ->implode("\n");
        $relationImports = $relationImports ? $relationImports."\n" : '';
        $createRelationData = $this->controllerRelationData($entity);
        $createReturn = $this->controllerCreateReturn($entity);
        $editReturn = $this->controllerEditReturn($entity);

        return <<<PHP
<?php

namespace App\Http\Controllers;

use App\Models\\{$entity['name']};
{$relationImports}use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class {$entity['name']}Controller extends Controller
{
    public function index(): View
    {
        \${$collection} = {$entity['name']}::query(){$with}->latest()->paginate(15);

        return view('{$route}.index', compact('{$collection}'));
    }

    public function create(): View
    {
{$createRelationData}
{$createReturn}
    }

    public function store(Request \$request): RedirectResponse
    {
        \${$variable} = {$entity['name']}::query()->create(\$this->validatedData(\$request));

        return redirect()->route('{$route}.show', \${$variable});
    }

    public function show({$entity['name']} \${$variable}): View
    {
        return view('{$route}.show', compact('{$variable}'));
    }

    public function edit({$entity['name']} \${$variable}): View
    {
{$createRelationData}
{$editReturn}
    }

    public function update(Request \$request, {$entity['name']} \${$variable}): RedirectResponse
    {
        \${$variable}->update(\$this->validatedData(\$request, \${$variable}->id));

        return redirect()->route('{$route}.show', \${$variable});
    }

    public function destroy({$entity['name']} \${$variable}): RedirectResponse
    {
        \${$variable}->delete();

        return redirect()->route('{$route}.index');
    }

    private function validatedData(Request \$request, ?int \$ignoreId = null): array
    {
        \$rules = [
{$rules}
        ];

        if (\$ignoreId) {
            foreach (\$rules as \$field => \$rule) {
                \$rules[\$field] = str_replace(','.\$field, ','.\$field.','.\$ignoreId, \$rule);
            }
        }

        return \$request->validate(\$rules);
    }
}

PHP;
    }

    private function controllerRelationData(array $entity): string
    {
        return collect($this->belongsToRelations($entity))
            ->map(fn (array $relation) => "        \${$relation['target_collection']} = {$relation['target']}::query()->orderBy('id')->get();")
            ->implode("\n");
    }

    private function controllerCreateReturn(array $entity): string
    {
        $collections = collect($this->belongsToRelations($entity))
            ->pluck('target_collection')
            ->map(fn (string $collection) => "'{$collection}'")
            ->implode(', ');

        if ($collections === '') {
            return "        return view('{$entity['route']}.create');";
        }

        return "        return view('{$entity['route']}.create', compact({$collections}));";
    }

    private function controllerEditReturn(array $entity): string
    {
        $compactItems = collect([$entity['variable']])
            ->merge(collect($this->belongsToRelations($entity))->pluck('target_collection'))
            ->map(fn (string $item) => "'{$item}'")
            ->implode(', ');

        return "        return view('{$entity['route']}.edit', compact({$compactItems}));";
    }

    private function migration(array $entity): string
    {
        $columns = collect($entity['fields'])
            ->map(fn (array $field) => '            '.$this->migrationColumn($field))
            ->merge(collect($this->belongsToRelations($entity))
                ->map(fn (array $relation) => '            '.$this->relationMigrationColumn($relation)))
            ->implode("\n");

        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$entity['table']}', function (Blueprint \$table) {
            \$table->id();
{$columns}
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$entity['table']}');
    }
};

PHP;
    }

    private function writeViews(string $outputDir, array $entity): void
    {
        $base = $outputDir.'/resources/views/'.$entity['route'];
        $this->write($base.'/index.blade.php', $this->indexView($entity));
        $this->write($base.'/create.blade.php', $this->formView($entity, false));
        $this->write($base.'/edit.blade.php', $this->formView($entity, true));
        $this->write($base.'/show.blade.php', $this->showView($entity));
    }

    private function indexView(array $entity): string
    {
        $headers = collect($entity['fields'])
            ->map(fn (array $field) => '<th>'.$field['label'].'</th>')
            ->merge(collect($this->belongsToRelations($entity))
                ->map(fn (array $relation) => '<th>'.$relation['target'].'</th>'))
            ->implode("\n                ");
        $cells = collect($entity['fields'])
            ->map(fn (array $field) => '<td>{{ $'.$entity['variable'].'->'.$field['name'].' }}</td>')
            ->merge(collect($this->belongsToRelations($entity))
                ->map(fn (array $relation) => '<td>{{ $'.$entity['variable'].'->'.$relation['method'].'?->displayName() ?? \'-\' }}</td>'))
            ->implode("\n                ");

        return <<<BLADE
@extends('layouts.app')

@section('content')
    <div class="page-header">
        <div>
            <p class="eyebrow">Records</p>
            <h1>{$entity['name']}</h1>
        </div>
        <a class="button primary" href="{{ route('{$entity['route']}.create') }}">Create</a>
    </div>

    <div class="card table-card">
        <table>
            <thead>
                <tr>
                    {$headers}
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach(\${$entity['collection']} as \${$entity['variable']})
                <tr>
                    {$cells}
                    <td class="actions">
                        <a href="{{ route('{$entity['route']}.show', \${$entity['variable']}) }}">Show</a>
                        <a href="{{ route('{$entity['route']}.edit', \${$entity['variable']}) }}">Edit</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="pagination">
        {{ \${$entity['collection']}->links() }}
    </div>
@endsection

BLADE;
    }

    private function formView(array $entity, bool $editing): string
    {
        $variable = $entity['variable'];
        $action = $editing
            ? "{{ route('{$entity['route']}.update', \${$variable}) }}"
            : "{{ route('{$entity['route']}.store') }}";
        $method = $editing ? "\n        @method('PUT')" : '';

        $inputs = collect($entity['fields'])
            ->map(function (array $field) use ($editing, $variable) {
                $value = $editing
                    ? "old('{$field['name']}', \${$variable}->{$field['name']})"
                    : "old('{$field['name']}')";
                $type = in_array($field['type'], ['integer', 'bigInteger', 'decimal'], true) ? 'number' : 'text';
                if ($field['type'] === 'date') {
                    $type = 'date';
                } elseif ($field['type'] === 'datetime') {
                    $type = 'datetime-local';
                } elseif ($field['type'] === 'email') {
                    $type = 'email';
                } elseif ($field['type'] === 'password') {
                    $type = 'password';
                }

                if ($field['type'] === 'text') {
                    return <<<BLADE
        <label>
            <span>{$field['label']}</span>
            <textarea name="{$field['name']}">{{ {$value} }}</textarea>
        </label>
        @error('{$field['name']}') <div class="error">{{ \$message }}</div> @enderror
BLADE;
                }

                return <<<BLADE
        <label>
            <span>{$field['label']}</span>
            <input type="{$type}" name="{$field['name']}" value="{{ {$value} }}">
        </label>
        @error('{$field['name']}') <div class="error">{{ \$message }}</div> @enderror
BLADE;
            })
            ->merge(collect($this->belongsToRelations($entity))
                ->map(fn (array $relation) => $this->relationSelectInput($relation, $editing, $variable)))
            ->implode("\n\n");

        $title = $editing ? 'Edit '.$entity['name'] : 'Create '.$entity['name'];

        return <<<BLADE
@extends('layouts.app')

@section('content')
    <div class="page-header">
        <div>
            <p class="eyebrow">Form</p>
            <h1>{$title}</h1>
        </div>
        <a class="button" href="{{ route('{$entity['route']}.index') }}">Back</a>
    </div>

    <form class="card form-card" method="POST" action="{$action}">
        @csrf{$method}

{$inputs}

        <button class="button primary" type="submit">Save</button>
    </form>
@endsection

BLADE;
    }

    private function showView(array $entity): string
    {
        $rows = collect($entity['fields'])
            ->map(fn (array $field) => "<dt>{$field['label']}</dt>\n        <dd>{{ \${$entity['variable']}->{$field['name']} }}</dd>")
            ->merge(collect($this->belongsToRelations($entity))
                ->map(fn (array $relation) => "<dt>{$relation['target']}</dt>\n        <dd>{{ \${$entity['variable']}->{$relation['method']}?->displayName() ?? '-' }}</dd>"))
            ->implode("\n        ");
        $hasManySections = collect($this->hasManyRelations($entity))
            ->map(fn (array $relation) => $this->hasManyShowSection($entity, $relation))
            ->implode("\n");

        return <<<BLADE
@extends('layouts.app')

@section('content')
    <div class="page-header">
        <div>
            <p class="eyebrow">Details</p>
            <h1>{$entity['name']}</h1>
        </div>
        <a class="button" href="{{ route('{$entity['route']}.index') }}">Back</a>
    </div>

    <dl class="card detail-list">
        {$rows}
    </dl>

{$hasManySections}

    <div class="actions-row">
        <a class="button primary" href="{{ route('{$entity['route']}.edit', \${$entity['variable']}) }}">Edit</a>
        <form method="POST" action="{{ route('{$entity['route']}.destroy', \${$entity['variable']}) }}">
        @csrf
        @method('DELETE')
            <button class="button danger" type="submit">Delete</button>
        </form>
    </div>
@endsection

BLADE;
    }

    private function relationSelectInput(array $relation, bool $editing, string $variable): string
    {
        $value = $editing
            ? "old('{$relation['foreign_key']}', \${$variable}->{$relation['foreign_key']})"
            : "old('{$relation['foreign_key']}')";

        return <<<BLADE
        <label>
            <span>{$relation['target']}</span>
            <select name="{$relation['foreign_key']}">
                <option value="">Choose {$relation['target']}</option>
                @foreach(\${$relation['target_collection']} as \${$relation['target_variable']})
                    <option value="{{ \${$relation['target_variable']}->id }}" @selected((string) {$value} === (string) \${$relation['target_variable']}->id)>
                        {{ \${$relation['target_variable']}->displayName() }}
                    </option>
                @endforeach
            </select>
        </label>
        @error('{$relation['foreign_key']}') <div class="error">{{ \$message }}</div> @enderror
BLADE;
    }

    private function hasManyShowSection(array $entity, array $relation): string
    {
        return <<<BLADE
    <section class="card related-card">
        <h2>{$relation['target']}</h2>
        <ul>
        @forelse(\${$entity['variable']}->{$relation['method']} as \${$relation['target_variable']})
            <li>{{ \${$relation['target_variable']}->displayName() }}</li>
        @empty
            <li>No related {$relation['target']} records.</li>
        @endforelse
        </ul>
    </section>

BLADE;
    }

    private function layout(string $appName, array $entities): string
    {
        $nav = collect($entities)
            ->map(fn (array $entity) => "<a href=\"{{ route('{$entity['route']}.index') }}\">{$entity['name']}</a>")
            ->implode("\n            ");

        return <<<BLADE
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$appName}</title>
    <style>
        :root { color-scheme: dark; font-family: Inter, ui-sans-serif, system-ui, sans-serif; background: #09090b; color: #f4f4f5; }
        body { margin: 0; background: linear-gradient(135deg, #09090b, #111827); min-height: 100vh; }
        a { color: inherit; }
        .shell { max-width: 1120px; margin: 0 auto; padding: 24px; }
        .topbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 16px 0 28px; }
        .brand { font-weight: 800; font-size: 18px; }
        .nav { display: flex; flex-wrap: wrap; gap: 8px; }
        .nav a, .button { border: 1px solid rgba(255,255,255,.12); border-radius: 8px; padding: 9px 13px; text-decoration: none; background: rgba(255,255,255,.05); font-weight: 700; font-size: 14px; }
        .button.primary { background: #6ee7b7; color: #052e2b; border-color: #6ee7b7; }
        .button.danger { background: rgba(248,113,113,.16); color: #fecaca; border-color: rgba(248,113,113,.35); }
        .page-header { display: flex; align-items: end; justify-content: space-between; gap: 16px; margin-bottom: 18px; }
        .eyebrow { margin: 0 0 6px; color: #a1a1aa; font-size: 12px; font-weight: 800; text-transform: uppercase; }
        h1 { margin: 0; font-size: 32px; line-height: 1.1; }
        h2 { margin-top: 0; }
        .card { border: 1px solid rgba(255,255,255,.10); border-radius: 10px; background: rgba(255,255,255,.055); box-shadow: 0 24px 80px rgba(0,0,0,.25); }
        .table-card { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 14px 16px; border-bottom: 1px solid rgba(255,255,255,.08); text-align: left; }
        th { color: #a1a1aa; font-size: 12px; text-transform: uppercase; }
        .actions, .actions-row { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .form-card { display: grid; gap: 16px; padding: 18px; max-width: 720px; }
        label { display: grid; gap: 7px; color: #d4d4d8; font-weight: 700; }
        input, textarea, select { width: 100%; box-sizing: border-box; border: 1px solid rgba(255,255,255,.12); border-radius: 8px; background: rgba(0,0,0,.35); color: #f4f4f5; padding: 11px 12px; }
        textarea { min-height: 120px; }
        .error { color: #fca5a5; font-size: 13px; }
        .detail-list { display: grid; grid-template-columns: minmax(140px, 220px) 1fr; gap: 0; overflow: hidden; margin-bottom: 18px; }
        .detail-list dt, .detail-list dd { margin: 0; padding: 14px 16px; border-bottom: 1px solid rgba(255,255,255,.08); }
        .detail-list dt { color: #a1a1aa; font-weight: 800; }
        .related-card { padding: 18px; margin-bottom: 18px; }
        .pagination { margin-top: 16px; color: #d4d4d8; }
    </style>
</head>
<body>
    <div class="shell">
        <header class="topbar">
            <div class="brand">{$appName}</div>
            <nav class="nav">
            {$nav}
            </nav>
        </header>

        @yield('content')
    </div>
</body>
</html>

BLADE;
    }

    private function migrationFileName(int $index, array $entity): string
    {
        return now()->addSeconds($index)->format('Y_m_d_His').'_create_'.$entity['table'].'_table';
    }

    private function migrationColumn(array $field): string
    {
        $column = match ($field['type']) {
            'bigInteger' => "\$table->bigInteger('{$field['name']}')",
            'boolean' => "\$table->boolean('{$field['name']}')",
            'date' => "\$table->date('{$field['name']}')",
            'datetime' => "\$table->dateTime('{$field['name']}')",
            'decimal' => "\$table->decimal('{$field['name']}', 10, 2)",
            'email', 'password', 'string' => "\$table->string('{$field['name']}')",
            'integer' => "\$table->integer('{$field['name']}')",
            'text' => "\$table->text('{$field['name']}')",
            default => "\$table->string('{$field['name']}')",
        };

        if (!$field['required']) {
            $column .= '->nullable()';
        }

        if ($field['unique']) {
            $column .= '->unique()';
        }

        return $column.';';
    }

    private function relationMigrationColumn(array $relation): string
    {
        return "\$table->foreignId('{$relation['foreign_key']}')->constrained('{$relation['target_table']}')->cascadeOnDelete();";
    }

    private function validationRule(string $type): string
    {
        return match ($type) {
            'bigInteger', 'integer' => 'integer',
            'boolean' => 'boolean',
            'date' => 'date',
            'datetime' => 'date',
            'decimal' => 'numeric',
            'email' => 'email',
            'password' => 'string|min:8',
            'text', 'string' => 'string',
            default => 'string',
        };
    }

    private function belongsToRelations(array $entity): array
    {
        return collect($entity['relations'] ?? [])
            ->where('type', 'belongsTo')
            ->values()
            ->all();
    }

    private function hasManyRelations(array $entity): array
    {
        return collect($entity['relations'] ?? [])
            ->where('type', 'hasMany')
            ->values()
            ->all();
    }

    private function sortEntitiesForMigrations(array $entities): array
    {
        $byName = collect($entities)->keyBy('name');
        $sorted = [];
        $visited = [];
        $visiting = [];

        $visit = function (array $entity) use (&$visit, &$sorted, &$visited, &$visiting, $byName): void {
            $name = $entity['name'];
            if (isset($visited[$name])) {
                return;
            }

            if (isset($visiting[$name])) {
                return;
            }

            $visiting[$name] = true;

            foreach ($this->belongsToRelations($entity) as $relation) {
                $target = $byName->get($relation['target']);
                if ($target) {
                    $visit($target);
                }
            }

            $visited[$name] = true;
            unset($visiting[$name]);
            $sorted[$name] = $entity;
        };

        foreach ($entities as $entity) {
            $visit($entity);
        }

        return array_values($sorted);
    }
}
