<?php

namespace App\Services\Generation;

use Illuminate\Support\Str;

class LaravelProjectGenerator
{
    private const ROOT_FILES = [
        '.editorconfig',
        '.gitattributes',
        '.gitignore',
        '.npmrc',
        'artisan',
        'package.json',
        'postcss.config.js',
        'tailwind.config.js',
        'vite.config.js',
    ];

    private const CONFIG_FILES = [
        'app.php',
        'auth.php',
        'cache.php',
        'database.php',
        'filesystems.php',
        'logging.php',
        'mail.php',
        'queue.php',
        'services.php',
        'session.php',
    ];

    public function generate(array $specification, string $outputDir): void
    {
        $this->ensureCleanDirectory($outputDir);

        $appName = $specification['app'];

        $this->writeBaseProject($outputDir, $appName);
        $this->write($outputDir.'/README.md', $this->readme($appName, $specification['entities']));
        $this->write($outputDir.'/routes/web.php', $this->routes($specification['entities']));
        $this->write($outputDir.'/routes/auth.php', $this->authRoutes());
        $this->write($outputDir.'/resources/views/layouts/app.blade.php', $this->layout($appName, $specification['entities']));
        $this->write($outputDir.'/resources/views/layouts/auth.blade.php', $this->authLayout($appName));
        $this->write($outputDir.'/resources/views/dashboard.blade.php', $this->dashboardView($appName, $specification['entities']));
        $this->write($outputDir.'/resources/views/auth/login.blade.php', $this->loginView());
        $this->write($outputDir.'/resources/views/auth/register.blade.php', $this->registerView());
        $this->write($outputDir.'/app/Http/Controllers/Auth/AuthenticatedSessionController.php', $this->authenticatedSessionController());
        $this->write($outputDir.'/app/Http/Controllers/Auth/RegisteredUserController.php', $this->registeredUserController());
        $this->write($outputDir.'/app/Http/Requests/Auth/LoginRequest.php', $this->loginRequest());
        $this->write($outputDir.'/tests/TestCase.php', $this->testCase());
        $this->write($outputDir.'/tests/Feature/ExampleTest.php', $this->featureExampleTest());
        $this->write($outputDir.'/tests/Unit/ExampleTest.php', $this->unitExampleTest());

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

        foreach ($this->belongsToManyPivotRelations($migrationEntities) as $index => $relation) {
            $this->write(
                $outputDir.'/database/migrations/'.$this->pivotMigrationFileName(count($migrationEntities) + $index, $relation).'.php',
                $this->pivotMigration($relation),
            );
        }
    }

    private function writeBaseProject(string $outputDir, string $appName): void
    {
        foreach (self::ROOT_FILES as $file) {
            $this->copyBaseFile($file, $outputDir.'/'.$file);
        }

        $databaseName = $this->databaseName($appName);

        $this->write($outputDir.'/composer.json', $this->composerJson($appName));
        $this->write($outputDir.'/.env.example', $this->envExample($appName));
        $this->write($outputDir.'/phpunit.xml', $this->phpunitXml($databaseName));

        $this->copyBaseFile('bootstrap/app.php', $outputDir.'/bootstrap/app.php');
        $this->copyBaseFile('bootstrap/providers.php', $outputDir.'/bootstrap/providers.php');
        $this->write($outputDir.'/bootstrap/cache/.gitignore', "*\n!.gitignore\n");

        foreach (self::CONFIG_FILES as $file) {
            $this->copyBaseFile('config/'.$file, $outputDir.'/config/'.$file);
        }

        foreach ([
            'app/Http/Controllers/Controller.php',
            'app/Models/User.php',
            'app/Providers/AppServiceProvider.php',
            'database/factories/UserFactory.php',
            'database/seeders/DatabaseSeeder.php',
            'public/.htaccess',
            'public/favicon.ico',
            'public/index.php',
            'public/robots.txt',
            'resources/css/app.css',
            'resources/js/app.js',
            'routes/console.php',
        ] as $file) {
            $this->copyBaseFile($file, $outputDir.'/'.$file);
        }

        foreach (glob(base_path('database/migrations/0001_*.php')) ?: [] as $migration) {
            $this->copyBaseFile(
                'database/migrations/'.basename($migration),
                $outputDir.'/database/migrations/'.basename($migration),
            );
        }

        foreach ([
            'database/.gitignore',
            'storage/app/.gitignore',
            'storage/app/private/.gitignore',
            'storage/app/public/.gitignore',
            'storage/framework/.gitignore',
            'storage/framework/cache/.gitignore',
            'storage/framework/cache/data/.gitignore',
            'storage/framework/sessions/.gitignore',
            'storage/framework/testing/.gitignore',
            'storage/framework/views/.gitignore',
            'storage/logs/.gitignore',
        ] as $file) {
            $this->copyBaseFile($file, $outputDir.'/'.$file);
        }
    }

    private function copyBaseFile(string $source, string $target): void
    {
        $sourcePath = base_path($source);
        if (!is_file($sourcePath)) {
            return;
        }

        $dir = dirname($target);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        copy($sourcePath, $target);
    }

    private function composerJson(string $appName): string
    {
        $packageName = strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $appName));
        $packageName = trim($packageName, '-') ?: 'generated-laravel-app';

        $composer = [
            '$schema' => 'https://getcomposer.org/schema.json',
            'name' => 'generated/'.$packageName,
            'type' => 'project',
            'description' => 'Generated Laravel application.',
            'license' => 'MIT',
            'require' => [
                'php' => '^8.4',
                'laravel/framework' => '^13.8',
                'laravel/tinker' => '^3.0',
            ],
            'require-dev' => [
                'fakerphp/faker' => '^1.23',
                'laravel/breeze' => '^2.4',
                'laravel/pail' => '^1.2.5',
                'laravel/pint' => '^1.27',
                'mockery/mockery' => '^1.6',
                'nunomaduro/collision' => '^8.6',
                'phpunit/phpunit' => '^12.5.12',
            ],
            'autoload' => [
                'psr-4' => [
                    'App\\' => 'app/',
                    'Database\\Factories\\' => 'database/factories/',
                    'Database\\Seeders\\' => 'database/seeders/',
                ],
            ],
            'autoload-dev' => [
                'psr-4' => [
                    'Tests\\' => 'tests/',
                ],
            ],
            'scripts' => [
                'setup' => [
                    'composer install',
                    '@php -r "file_exists(\'.env\') || copy(\'.env.example\', \'.env\');"',
                    '@php artisan key:generate',
                    '@php artisan migrate --force',
                    'npm install',
                    'npm run build',
                ],
                'dev' => [
                    'Composer\\Config::disableProcessTimeout',
                    'npx concurrently -c "#93c5fd,#c4b5fd,#fb7185,#fdba74" "php artisan serve" "npm run dev" --names=server,vite --kill-others',
                ],
                'test' => [
                    '@php artisan config:clear --ansi',
                    '@php artisan test',
                ],
                'post-autoload-dump' => [
                    'Illuminate\\Foundation\\ComposerScripts::postAutoloadDump',
                    '@php artisan package:discover --ansi',
                ],
                'post-update-cmd' => [
                    '@php artisan vendor:publish --tag=laravel-assets --ansi --force',
                ],
                'post-root-package-install' => [
                    '@php -r "file_exists(\'.env\') || copy(\'.env.example\', \'.env\');"',
                ],
                'post-create-project-cmd' => [
                    '@php artisan key:generate --ansi',
                    '@php artisan migrate --graceful --ansi',
                ],
            ],
            'extra' => [
                'laravel' => [
                    'dont-discover' => [],
                ],
            ],
            'config' => [
                'optimize-autoloader' => true,
                'preferred-install' => 'dist',
                'sort-packages' => true,
            ],
            'minimum-stability' => 'stable',
            'prefer-stable' => true,
        ];

        return json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
    }

    private function envExample(string $appName): string
    {
        $quotedAppName = str_contains($appName, ' ')
            ? '"'.addcslashes($appName, "\"\\").'"'
            : $appName;
        $databaseName = $this->databaseName($appName);

        return <<<ENV
APP_NAME={$quotedAppName}
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE={$databaseName}
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="\${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="\${APP_NAME}"

ENV;
    }

    private function phpunitXml(string $databaseName): string
    {
        $testDatabaseName = $databaseName.'_test';

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
>
    <testsuites>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>app</directory>
        </include>
    </source>
    <php>
        <server name="APP_ENV" value="testing"/>
        <server name="APP_KEY" value="base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA="/>
        <server name="BCRYPT_ROUNDS" value="4"/>
        <server name="CACHE_STORE" value="array"/>
        <server name="DB_CONNECTION" value="mysql"/>
        <server name="DB_HOST" value="127.0.0.1"/>
        <server name="DB_PORT" value="3306"/>
        <server name="DB_DATABASE" value="{$testDatabaseName}"/>
        <server name="DB_USERNAME" value="root"/>
        <server name="DB_PASSWORD" value=""/>
        <server name="MAIL_MAILER" value="array"/>
        <server name="QUEUE_CONNECTION" value="sync"/>
        <server name="SESSION_DRIVER" value="array"/>
    </php>
</phpunit>

XML;
    }

    private function databaseName(string $appName): string
    {
        $name = strtolower((string) preg_replace('/[^a-z0-9]+/i', '_', $appName));
        $name = trim($name, '_');

        return $name !== '' ? $name : 'generated_laravel_app';
    }

    private function indent(string $content, int $spaces): string
    {
        $prefix = str_repeat(' ', $spaces);

        return collect(explode("\n", $content))
            ->map(fn (string $line) => $line === '' ? $line : $prefix.$line)
            ->implode("\n");
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
        $databaseName = $this->databaseName($appName);
        $list = collect($entities)
            ->map(function (array $entity) {
                $relations = collect($entity['relations'] ?? [])
                    ->map(fn (array $relation) => $relation['type'].' '.$relation['target'])
                    ->implode(', ');

                return '- '.$entity['name'].' (`'.$entity['table'].'`)'.($relations ? ' - relations: '.$relations : '');
            })
            ->implode("\n");

        return <<<MD
# {$appName}

This is a complete Laravel application generated from a DSL specification. It includes a Laravel application skeleton, basic authentication, database migrations, Eloquent models, resource controllers, web routes, and Blade CRUD views.

## Generated Entities

{$list}

## Requirements

- PHP 8.4 or newer
- Composer
- Node.js and npm
- MySQL or MariaDB

## Setup

1. Install PHP dependencies:

```bash
composer install
```

2. Install JavaScript dependencies:

```bash
npm install
```

3. Create the environment file and application key:

```bash
cp .env.example .env
php artisan key:generate
```

4. Create the MySQL database:

```bash
mysql -u root -p -e "CREATE DATABASE {$databaseName} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

If your MySQL username, password, host, or database name is different, update the `DB_*` values in `.env` before running migrations.

5. Run database migrations:

```bash
php artisan migrate
```

6. Start the Laravel server:

```bash
php artisan serve
```

7. In a second terminal, start Vite:

```bash
npm run dev
```

Open `http://127.0.0.1:8000`, register a user, then use the generated CRUD screens from the dashboard.

## Production Build

```bash
npm run build
php artisan config:cache
php artisan route:cache
```

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
    return redirect()->route('dashboard');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
{$this->indent($resourceRoutes, 4)}
    Route::get('/generated', function () {
        return redirect()->route('{$firstRoute}.index');
    })->name('generated.index');
});

require __DIR__.'/auth.php';

PHP;
    }

    private function authRoutes(): string
    {
        return <<<'PHP'
<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});

PHP;
    }

    private function model(array $entity): string
    {
        $fillable = collect($this->fillableAttributes($entity))
            ->map(fn (string $attribute) => "        '{$attribute}',")
            ->implode("\n");
        $casts = collect($this->fieldCasts($entity))
            ->map(fn (string $cast, string $attribute) => "        '{$attribute}' => '{$cast}',")
            ->implode("\n");
        $hidden = collect($this->hiddenAttributes($entity))
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

        $castsBlock = $casts ? "\n    protected \$casts = [\n{$casts}\n    ];\n" : '';
        $hiddenBlock = $hidden ? "\n    protected \$hidden = [\n{$hidden}\n    ];\n" : '';
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
{$castsBlock}{$hiddenBlock}
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

    private function fieldCasts(array $entity): array
    {
        return collect($entity['fields'])
            ->mapWithKeys(function (array $field): array {
                $cast = match ($field['type']) {
                    'bigInteger', 'integer' => 'integer',
                    'boolean' => 'boolean',
                    'date' => 'date',
                    'datetime' => 'datetime',
                    'decimal' => 'decimal:2',
                    'password' => 'hashed',
                    default => null,
                };

                return $cast ? [$field['name'] => $cast] : [];
            })
            ->all();
    }

    private function hiddenAttributes(array $entity): array
    {
        return collect($entity['fields'])
            ->where('type', 'password')
            ->pluck('name')
            ->all();
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

        if ($relation['type'] === 'hasOne') {
            return <<<PHP

    public function {$method}()
    {
        return \$this->hasOne({$target}::class);
    }
PHP;
        }

        if ($relation['type'] === 'belongsToMany') {
            return <<<PHP

    public function {$method}()
    {
        return \$this->belongsToMany({$target}::class)->withTimestamps();
    }
PHP;
        }

        return '';
    }

    private function controller(array $entity): string
    {
        $rules = $this->controllerValidationRules($entity);
        $route = $entity['route'];
        $variable = $entity['variable'];
        $collection = $entity['collection'];
        $withRelations = collect($this->eagerLoadRelations($entity))->pluck('method')->all();
        $with = $withRelations === []
            ? ''
            : "->with(['".implode("', '", $withRelations)."'])";
        $relationImports = collect($this->formRelations($entity))
            ->pluck('target')
            ->unique()
            ->reject(fn (string $target) => $target === $entity['name'])
            ->map(fn (string $target) => "use App\\Models\\{$target};")
            ->implode("\n");
        $relationImports = $relationImports ? $relationImports."\n" : '';
        $createRelationData = $this->controllerRelationData($entity);
        $createReturn = $this->controllerCreateReturn($entity);
        $editReturn = $this->controllerEditReturn($entity);
        $relationKeys = collect($this->belongsToManyRelations($entity))
            ->pluck('method')
            ->map(fn (string $method) => "'{$method}'")
            ->implode(', ');
        $relationKeys = $relationKeys === '' ? '' : $relationKeys;
        $showRelations = collect($this->showRelations($entity))->pluck('method')->all();
        $showLoad = $showRelations === []
            ? ''
            : "        \${$variable}->loadMissing(['".implode("', '", $showRelations)."']);\n\n";
        $syncRelationships = $this->syncRelationshipsMethod($entity);
        $passwordFields = collect($entity['fields'])
            ->where('type', 'password')
            ->pluck('name')
            ->map(fn (string $field) => "'{$field}'")
            ->implode(', ');
        $passwordFields = $passwordFields === '' ? '' : $passwordFields;
        $passwordUpdateRules = collect($entity['fields'])
            ->where('type', 'password')
            ->pluck('name')
            ->map(fn (string $field) => "                \$rules['{$field}'] = str_replace('required|', 'nullable|', \$rules['{$field}']);")
            ->implode("\n");
        $passwordUpdateRules = $passwordUpdateRules ? "\n{$passwordUpdateRules}" : '';
        $passwordCleanup = $passwordFields === ''
            ? ''
            : <<<PHP

        foreach ([{$passwordFields}] as \$passwordField) {
            if ((\$attributes[\$passwordField] ?? null) === null || \$attributes[\$passwordField] === '') {
                \$attributes->forget(\$passwordField);
            }
        }
PHP;

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
        \$validated = \$this->validatedData(\$request);
        \${$variable} = {$entity['name']}::query()->create(\$validated['attributes']);
        \$this->syncRelationships(\${$variable}, \$validated['relations']);

        return redirect()->route('{$route}.show', \${$variable});
    }

    public function show({$entity['name']} \${$variable}): View
    {
{$showLoad}
        return view('{$route}.show', compact('{$variable}'));
    }

    public function edit({$entity['name']} \${$variable}): View
    {
{$createRelationData}
{$editReturn}
    }

    public function update(Request \$request, {$entity['name']} \${$variable}): RedirectResponse
    {
        \$validated = \$this->validatedData(\$request, \${$variable}->id);
        \${$variable}->update(\$validated['attributes']);
        \$this->syncRelationships(\${$variable}, \$validated['relations']);

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
{$passwordUpdateRules}
        }

        \$validated = \$request->validate(\$rules);
        \$attributes = collect(\$validated)->except([{$relationKeys}]);{$passwordCleanup}

        return [
            'attributes' => \$attributes->all(),
            'relations' => collect(\$validated)->only([{$relationKeys}])->all(),
        ];
    }

{$syncRelationships}
}

PHP;
    }

    private function controllerRelationData(array $entity): string
    {
        return collect($this->formRelations($entity))
            ->map(fn (array $relation) => "        \${$relation['target_collection']} = {$relation['target']}::query()->orderBy('id')->get();")
            ->implode("\n");
    }

    private function controllerCreateReturn(array $entity): string
    {
        $collections = collect($this->formRelations($entity))
            ->pluck('target_collection')
            ->unique()
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
            ->merge(collect($this->formRelations($entity))->pluck('target_collection'))
            ->unique()
            ->map(fn (string $item) => "'{$item}'")
            ->implode(', ');

        return "        return view('{$entity['route']}.edit', compact({$compactItems}));";
    }

    private function controllerValidationRules(array $entity): string
    {
        return collect($entity['fields'])
            ->map(function (array $field) use ($entity) {
                $rule = $field['required'] ? 'required' : 'nullable';
                $rule .= '|'.$this->validationRule($field['type']);
                if ($field['unique']) {
                    $rule .= '|unique:'.$entity['table'].','.$field['name'];
                }

                return "            '{$field['name']}' => '{$rule}',";
            })
            ->merge(collect($this->belongsToRelations($entity))
                ->map(fn (array $relation) => "            '{$relation['foreign_key']}' => 'required|integer|exists:{$relation['target_table']},id',"))
            ->merge(collect($this->belongsToManyRelations($entity))
                ->flatMap(fn (array $relation) => [
                    "            '{$relation['method']}' => 'nullable|array',",
                    "            '{$relation['method']}.*' => 'integer|exists:{$relation['target_table']},id',",
                ]))
            ->implode("\n");
    }

    private function syncRelationshipsMethod(array $entity): string
    {
        $syncLines = collect($this->belongsToManyRelations($entity))
            ->map(fn (array $relation) => "        \${$entity['variable']}->{$relation['method']}()->sync(\$relations['{$relation['method']}'] ?? []);")
            ->implode("\n");

        if ($syncLines === '') {
            $syncLines = '        // This model does not define many-to-many relationships.';
        }

        return <<<PHP
    private function syncRelationships({$entity['name']} \${$entity['variable']}, array \$relations): void
    {
{$syncLines}
    }

PHP;
    }

    private function formRelations(array $entity): array
    {
        return collect($entity['relations'] ?? [])
            ->whereIn('type', ['belongsTo', 'belongsToMany'])
            ->values()
            ->all();
    }

    private function eagerLoadRelations(array $entity): array
    {
        return collect($entity['relations'] ?? [])
            ->whereIn('type', ['belongsTo', 'belongsToMany', 'hasOne'])
            ->values()
            ->all();
    }

    private function showRelations(array $entity): array
    {
        return collect($entity['relations'] ?? [])
            ->whereIn('type', ['belongsTo', 'belongsToMany', 'hasMany', 'hasOne'])
            ->values()
            ->all();
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

    private function pivotMigration(array $relation): string
    {
        $models = $relation['pivot_models'];
        $firstModel = $models[0];
        $secondModel = $models[1];
        $firstKey = Str::snake($firstModel).'_id';
        $secondKey = Str::snake($secondModel).'_id';
        $firstTable = Str::snake(Str::pluralStudly($firstModel));
        $secondTable = Str::snake(Str::pluralStudly($secondModel));

        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$relation['pivot_table']}', function (Blueprint \$table) {
            \$table->id();
            \$table->foreignId('{$firstKey}')->constrained('{$firstTable}')->cascadeOnDelete();
            \$table->foreignId('{$secondKey}')->constrained('{$secondTable}')->cascadeOnDelete();
            \$table->unique(['{$firstKey}', '{$secondKey}']);
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$relation['pivot_table']}');
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
            ->merge(collect($this->indexRelations($entity))
                ->map(fn (array $relation) => '<th>'.$relation['target'].'</th>'))
            ->implode("\n                ");
        $cells = collect($entity['fields'])
            ->map(fn (array $field) => $this->indexFieldCell($entity, $field))
            ->merge(collect($this->indexRelations($entity))
                ->map(fn (array $relation) => $this->indexRelationCell($entity, $relation)))
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
            ->map(fn (array $field) => $this->fieldInput($field, $editing, $variable))
            ->merge(collect($this->belongsToRelations($entity))
                ->map(fn (array $relation) => $this->relationSelectInput($relation, $editing, $variable)))
            ->merge(collect($this->belongsToManyRelations($entity))
                ->map(fn (array $relation) => $this->manyRelationSelectInput($relation, $editing, $variable)))
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
            ->map(fn (array $field) => "<dt>{$field['label']}</dt>\n        <dd>".$this->fieldDisplayValue($entity, $field).'</dd>')
            ->merge(collect($this->belongsToRelations($entity))
                ->map(fn (array $relation) => "<dt>{$relation['target']}</dt>\n        <dd>{{ \${$entity['variable']}->{$relation['method']}?->displayName() ?? '-' }}</dd>"))
            ->merge(collect($this->hasOneRelations($entity))
                ->map(fn (array $relation) => "<dt>{$relation['target']}</dt>\n        <dd>{{ \${$entity['variable']}->{$relation['method']}?->displayName() ?? '-' }}</dd>"))
            ->merge(collect($this->belongsToManyRelations($entity))
                ->map(fn (array $relation) => "<dt>{$relation['target']}</dt>\n        <dd>{{ \${$entity['variable']}->{$relation['method']}->map->displayName()->join(', ') ?: '-' }}</dd>"))
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

    private function fieldInput(array $field, bool $editing, string $variable): string
    {
        $value = $this->fieldInputValue($field, $editing, $variable);

        if ($field['type'] === 'boolean') {
            return <<<BLADE
        <label>
            <span>{$field['label']}</span>
            <select name="{$field['name']}">
                <option value="">Choose {$field['label']}</option>
                <option value="1" @selected((string) {$value} === '1')>Yes</option>
                <option value="0" @selected((string) {$value} === '0')>No</option>
            </select>
        </label>
        @error('{$field['name']}') <div class="error">{{ \$message }}</div> @enderror
BLADE;
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

        $type = match ($field['type']) {
            'bigInteger', 'decimal', 'integer' => 'number',
            'date' => 'date',
            'datetime' => 'datetime-local',
            'email' => 'email',
            'password' => 'password',
            default => 'text',
        };
        $step = match ($field['type']) {
            'bigInteger', 'integer' => ' step="1"',
            'decimal' => ' step="0.01"',
            default => '',
        };
        $autocomplete = $field['type'] === 'password' ? ' autocomplete="new-password"' : '';
        $valueAttribute = $field['type'] === 'password' && $editing
            ? ''
            : ' value="{{ '.$value.' }}"';

        return <<<BLADE
        <label>
            <span>{$field['label']}</span>
            <input type="{$type}" name="{$field['name']}"{$step}{$autocomplete}{$valueAttribute}>
        </label>
        @error('{$field['name']}') <div class="error">{{ \$message }}</div> @enderror
BLADE;
    }

    private function fieldInputValue(array $field, bool $editing, string $variable): string
    {
        if (!$editing || $field['type'] === 'password') {
            return "old('{$field['name']}')";
        }

        return match ($field['type']) {
            'boolean' => "old('{$field['name']}', \${$variable}->{$field['name']} === null ? '' : (string) (int) \${$variable}->{$field['name']})",
            'date' => "old('{$field['name']}', optional(\${$variable}->{$field['name']})->format('Y-m-d'))",
            'datetime' => "old('{$field['name']}', optional(\${$variable}->{$field['name']})->format('Y-m-d\\TH:i'))",
            default => "old('{$field['name']}', \${$variable}->{$field['name']})",
        };
    }

    private function indexFieldCell(array $entity, array $field): string
    {
        return '<td>'.$this->fieldDisplayValue($entity, $field).'</td>';
    }

    private function fieldDisplayValue(array $entity, array $field): string
    {
        $variable = $entity['variable'];
        $name = $field['name'];

        return match ($field['type']) {
            'boolean' => "{{ \${$variable}->{$name} === null ? '-' : (\${$variable}->{$name} ? 'Yes' : 'No') }}",
            'date' => "{{ optional(\${$variable}->{$name})->format('Y-m-d') ?? '-' }}",
            'datetime' => "{{ optional(\${$variable}->{$name})->format('Y-m-d H:i') ?? '-' }}",
            'password' => "{{ \${$variable}->{$name} ? 'Set' : '-' }}",
            default => "{{ \${$variable}->{$name} }}",
        };
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

    private function manyRelationSelectInput(array $relation, bool $editing, string $variable): string
    {
        $selectedExpression = $editing
            ? "collect(old('{$relation['method']}', \${$variable}->{$relation['method']}->pluck('id')->all()))"
            : "collect(old('{$relation['method']}', []))";

        return <<<BLADE
        @php(\$selected{$relation['target_collection']} = {$selectedExpression}->map(fn (\$id) => (string) \$id))
        <label>
            <span>{$relation['target']}</span>
            <select name="{$relation['method']}[]" multiple>
                @foreach(\${$relation['target_collection']} as \${$relation['target_variable']})
                    <option value="{{ \${$relation['target_variable']}->id }}" @selected(\$selected{$relation['target_collection']}->contains((string) \${$relation['target_variable']}->id))>
                        {{ \${$relation['target_variable']}->displayName() }}
                    </option>
                @endforeach
            </select>
        </label>
        @error('{$relation['method']}') <div class="error">{{ \$message }}</div> @enderror
        @error('{$relation['method']}.*') <div class="error">{{ \$message }}</div> @enderror
BLADE;
    }

    private function indexRelationCell(array $entity, array $relation): string
    {
        if (in_array($relation['type'], ['belongsTo', 'hasOne'], true)) {
            return '<td>{{ $'.$entity['variable'].'->'.$relation['method'].'?->displayName() ?? \'-\' }}</td>';
        }

        if ($relation['type'] === 'belongsToMany') {
            return '<td>{{ $'.$entity['variable'].'->'.$relation['method'].'->map->displayName()->join(\', \') ?: \'-\' }}</td>';
        }

        if ($relation['type'] === 'hasMany') {
            return '<td>{{ $'.$entity['variable'].'->'.$relation['method'].'->count() }}</td>';
        }

        return '<td>-</td>';
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
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{$appName}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root { color-scheme: dark; font-family: Inter, ui-sans-serif, system-ui, sans-serif; background: #0b0f14; color: #f4f4f5; }
        body { margin: 0; background: #0b0f14; min-height: 100vh; }
        a { color: inherit; }
        .shell { max-width: 1120px; margin: 0 auto; padding: 24px; }
        .topbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 16px 0 28px; }
        .brand { font-weight: 800; font-size: 18px; }
        .nav { display: flex; flex-wrap: wrap; gap: 8px; }
        .nav a, .button { border: 1px solid rgba(255,255,255,.12); border-radius: 8px; padding: 9px 13px; text-decoration: none; background: rgba(255,255,255,.05); color: #f4f4f5; font-weight: 700; font-size: 14px; cursor: pointer; }
        .button.primary { background: #6ee7b7; color: #052e2b; border-color: #6ee7b7; }
        .button.danger { background: rgba(248,113,113,.16); color: #fecaca; border-color: rgba(248,113,113,.35); }
        .page-header { display: flex; align-items: end; justify-content: space-between; gap: 16px; margin-bottom: 18px; }
        .eyebrow { margin: 0 0 6px; color: #a1a1aa; font-size: 12px; font-weight: 800; text-transform: uppercase; }
        h1 { margin: 0; font-size: 32px; line-height: 1.1; }
        h2 { margin-top: 0; }
        .card { border: 1px solid rgba(255,255,255,.10); border-radius: 8px; background: rgba(255,255,255,.055); box-shadow: 0 24px 80px rgba(0,0,0,.25); }
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
        .top-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .muted { color: #a1a1aa; }
        form.inline { margin: 0; }
    </style>
</head>
<body>
    <div class="shell">
        <header class="topbar">
            <a class="brand" href="{{ route('dashboard') }}">{$appName}</a>
            <div class="top-actions">
                @auth
                    <nav class="nav">
                    {$nav}
                    </nav>
                    <span class="muted">{{ auth()->user()->name }}</span>
                    <form class="inline" method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="button" type="submit">Log out</button>
                    </form>
                @else
                    <a class="button" href="{{ route('login') }}">Log in</a>
                    <a class="button primary" href="{{ route('register') }}">Register</a>
                @endauth
            </div>
        </header>

        @hasSection('content')
            @yield('content')
        @else
            {{ \$slot ?? '' }}
        @endif
    </div>
</body>
</html>

BLADE;
    }

    private function authLayout(string $appName): string
    {
        return <<<BLADE
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{$appName}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root { color-scheme: dark; font-family: Inter, ui-sans-serif, system-ui, sans-serif; background: #0b0f14; color: #f4f4f5; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #0b0f14; padding: 24px; }
        a { color: #6ee7b7; }
        .auth-card { width: min(100%, 440px); border: 1px solid rgba(255,255,255,.10); border-radius: 8px; background: rgba(255,255,255,.055); padding: 28px; box-shadow: 0 24px 80px rgba(0,0,0,.25); }
        .brand { display: inline-block; margin-bottom: 22px; color: #f4f4f5; font-weight: 800; text-decoration: none; }
        h1 { margin: 0 0 8px; font-size: 30px; line-height: 1.1; }
        p { margin: 0 0 22px; color: #a1a1aa; }
        form { display: grid; gap: 16px; }
        label { display: grid; gap: 7px; color: #d4d4d8; font-weight: 700; }
        input { width: 100%; box-sizing: border-box; border: 1px solid rgba(255,255,255,.12); border-radius: 8px; background: rgba(0,0,0,.35); color: #f4f4f5; padding: 11px 12px; }
        .row { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
        .button { border: 1px solid #6ee7b7; border-radius: 8px; padding: 10px 14px; background: #6ee7b7; color: #052e2b; font-weight: 800; cursor: pointer; }
        .error { color: #fca5a5; font-size: 13px; }
        .status { color: #86efac; font-size: 14px; }
    </style>
</head>
<body>
    <main class="auth-card">
        <a class="brand" href="{{ url('/') }}">{$appName}</a>
        @yield('content')
    </main>
</body>
</html>

BLADE;
    }

    private function dashboardView(string $appName, array $entities): string
    {
        $links = collect($entities)
            ->map(fn (array $entity) => "        <a class=\"button primary\" href=\"{{ route('{$entity['route']}.index') }}\">Open {$entity['name']}</a>")
            ->implode("\n");

        return <<<BLADE
@extends('layouts.app')

@section('content')
    <div class="page-header">
        <div>
            <p class="eyebrow">Dashboard</p>
            <h1>{$appName}</h1>
        </div>
    </div>

    <section class="card related-card">
        <h2>Generated CRUD</h2>
        <p class="muted">Choose a resource to manage records.</p>
        <div class="actions-row">
{$links}
        </div>
    </section>
@endsection

BLADE;
    }

    private function loginView(): string
    {
        return <<<'BLADE'
@extends('layouts.auth')

@section('content')
    <h1>Log in</h1>
    <p>Access the generated Laravel application.</p>

    @if (session('status'))
        <div class="status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <label>
            <span>Email</span>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
        </label>
        @error('email') <div class="error">{{ $message }}</div> @enderror

        <label>
            <span>Password</span>
            <input type="password" name="password" required autocomplete="current-password">
        </label>
        @error('password') <div class="error">{{ $message }}</div> @enderror

        <label>
            <span>
                <input type="checkbox" name="remember">
                Remember me
            </span>
        </label>

        <div class="row">
            <a href="{{ route('register') }}">Create an account</a>
            <button class="button" type="submit">Log in</button>
        </div>
    </form>
@endsection

BLADE;
    }

    private function registerView(): string
    {
        return <<<'BLADE'
@extends('layouts.auth')

@section('content')
    <h1>Register</h1>
    <p>Create the first user for this generated application.</p>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <label>
            <span>Name</span>
            <input type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name">
        </label>
        @error('name') <div class="error">{{ $message }}</div> @enderror

        <label>
            <span>Email</span>
            <input type="email" name="email" value="{{ old('email') }}" required autocomplete="username">
        </label>
        @error('email') <div class="error">{{ $message }}</div> @enderror

        <label>
            <span>Password</span>
            <input type="password" name="password" required autocomplete="new-password">
        </label>
        @error('password') <div class="error">{{ $message }}</div> @enderror

        <label>
            <span>Confirm password</span>
            <input type="password" name="password_confirmation" required autocomplete="new-password">
        </label>

        <div class="row">
            <a href="{{ route('login') }}">Already registered?</a>
            <button class="button" type="submit">Register</button>
        </div>
    </form>
@endsection

BLADE;
    }

    private function authenticatedSessionController(): string
    {
        return <<<'PHP'
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}

PHP;
    }

    private function registeredUserController(): string
    {
        return <<<'PHP'
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}

PHP;
    }

    private function loginRequest(): string
    {
        return <<<'PHP'
<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}

PHP;
    }

    private function testCase(): string
    {
        return <<<'PHP'
<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    //
}

PHP;
    }

    private function featureExampleTest(): string
    {
        return <<<'PHP'
<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_home_redirects_to_dashboard(): void
    {
        $this->get('/')
            ->assertRedirect(route('dashboard'));
    }
}

PHP;
    }

    private function unitExampleTest(): string
    {
        return <<<'PHP'
<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    public function test_that_true_is_true(): void
    {
        $this->assertTrue(true);
    }
}

PHP;
    }

    private function migrationFileName(int $index, array $entity): string
    {
        return now()->addSeconds($index)->format('Y_m_d_His').'_create_'.$entity['table'].'_table';
    }

    private function pivotMigrationFileName(int $index, array $relation): string
    {
        return now()->addSeconds($index)->format('Y_m_d_His').'_create_'.$relation['pivot_table'].'_table';
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

    private function hasOneRelations(array $entity): array
    {
        return collect($entity['relations'] ?? [])
            ->where('type', 'hasOne')
            ->values()
            ->all();
    }

    private function belongsToManyRelations(array $entity): array
    {
        return collect($entity['relations'] ?? [])
            ->where('type', 'belongsToMany')
            ->values()
            ->all();
    }

    private function indexRelations(array $entity): array
    {
        return collect($entity['relations'] ?? [])
            ->whereIn('type', ['belongsTo', 'belongsToMany', 'hasMany', 'hasOne'])
            ->values()
            ->all();
    }

    private function belongsToManyPivotRelations(array $entities): array
    {
        return collect($entities)
            ->flatMap(fn (array $entity) => $this->belongsToManyRelations($entity))
            ->unique('pivot_table')
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
