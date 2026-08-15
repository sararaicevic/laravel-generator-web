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
        $this->write($outputDir.'/resources/views/layouts/app.blade.php', $this->layout($appName));

        foreach ($specification['entities'] as $index => $entity) {
            $this->write($outputDir.'/app/Models/'.$entity['name'].'.php', $this->model($entity));
            $this->write($outputDir.'/app/Http/Controllers/'.$entity['name'].'Controller.php', $this->controller($entity));
            $this->write(
                $outputDir.'/database/migrations/'.$this->migrationFileName($index, $entity).'.php',
                $this->migration($entity),
            );
            $this->writeViews($outputDir, $entity);
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

        mkdir($dir, 0775, true);
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
            ->map(fn (array $entity) => '- '.$entity['name'].' (`'.$entity['table'].'`)')
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
        $fillable = collect($entity['fields'])
            ->map(fn (array $field) => "        '{$field['name']}',")
            ->implode("\n");

        return <<<PHP
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class {$entity['name']} extends Model
{
    protected \$fillable = [
{$fillable}
    ];
}

PHP;
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
            ->implode("\n");

        $route = $entity['route'];
        $variable = $entity['variable'];
        $collection = $entity['collection'];

        return <<<PHP
<?php

namespace App\Http\Controllers;

use App\Models\\{$entity['name']};
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class {$entity['name']}Controller extends Controller
{
    public function index(): View
    {
        \${$collection} = {$entity['name']}::query()->latest()->paginate(15);

        return view('{$route}.index', compact('{$collection}'));
    }

    public function create(): View
    {
        return view('{$route}.create');
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
        return view('{$route}.edit', compact('{$variable}'));
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

    private function migration(array $entity): string
    {
        $columns = collect($entity['fields'])
            ->map(fn (array $field) => '            '.$this->migrationColumn($field))
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
            ->implode("\n                ");
        $cells = collect($entity['fields'])
            ->map(fn (array $field) => '<td>{{ $'.$entity['variable'].'->'.$field['name'].' }}</td>')
            ->implode("\n                ");

        return <<<BLADE
@extends('layouts.app')

@section('content')
    <h1>{$entity['name']}</h1>
    <a href="{{ route('{$entity['route']}.create') }}">Create</a>

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
                <td>
                    <a href="{{ route('{$entity['route']}.show', \${$entity['variable']}) }}">Show</a>
                    <a href="{{ route('{$entity['route']}.edit', \${$entity['variable']}) }}">Edit</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{ \${$entity['collection']}->links() }}
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
            {$field['label']}
            <textarea name="{$field['name']}">{{ {$value} }}</textarea>
        </label>
        @error('{$field['name']}') <div>{{ \$message }}</div> @enderror
BLADE;
                }

                return <<<BLADE
        <label>
            {$field['label']}
            <input type="{$type}" name="{$field['name']}" value="{{ {$value} }}">
        </label>
        @error('{$field['name']}') <div>{{ \$message }}</div> @enderror
BLADE;
            })
            ->implode("\n\n");

        $title = $editing ? 'Edit '.$entity['name'] : 'Create '.$entity['name'];

        return <<<BLADE
@extends('layouts.app')

@section('content')
    <h1>{$title}</h1>

    <form method="POST" action="{$action}">
        @csrf{$method}

{$inputs}

        <button type="submit">Save</button>
    </form>
@endsection

BLADE;
    }

    private function showView(array $entity): string
    {
        $rows = collect($entity['fields'])
            ->map(fn (array $field) => "<dt>{$field['label']}</dt>\n        <dd>{{ \${$entity['variable']}->{$field['name']} }}</dd>")
            ->implode("\n        ");

        return <<<BLADE
@extends('layouts.app')

@section('content')
    <h1>{$entity['name']} Details</h1>

    <dl>
        {$rows}
    </dl>

    <a href="{{ route('{$entity['route']}.edit', \${$entity['variable']}) }}">Edit</a>
    <form method="POST" action="{{ route('{$entity['route']}.destroy', \${$entity['variable']}) }}">
        @csrf
        @method('DELETE')
        <button type="submit">Delete</button>
    </form>
@endsection

BLADE;
    }

    private function layout(string $appName): string
    {
        return <<<BLADE
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$appName}</title>
</head>
<body>
    @yield('content')
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
}
