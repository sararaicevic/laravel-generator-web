<?php

namespace Tests\Feature;

use App\Services\Generation\LaravelProjectGenerator;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class LaravelProjectGeneratorTest extends TestCase
{
    private string $outputDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outputDir = storage_path('app/testing/generated-laravel-project');
        File::deleteDirectory($this->outputDir);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->outputDir);

        parent::tearDown();
    }

    public function test_it_generates_a_complete_laravel_application_skeleton(): void
    {
        (new LaravelProjectGenerator())->generate($this->specification(), $this->outputDir);

        foreach ([
            'artisan',
            'bootstrap/app.php',
            'composer.json',
            'package.json',
            '.env.example',
            'public/index.php',
            'routes/auth.php',
            'routes/web.php',
            'app/Models/User.php',
            'app/Http/Controllers/Auth/AuthenticatedSessionController.php',
            'app/Http/Controllers/Auth/RegisteredUserController.php',
            'resources/views/auth/login.blade.php',
            'resources/views/auth/register.blade.php',
            'resources/views/dashboard.blade.php',
            'database/migrations/0001_01_01_000000_create_users_table.php',
            'database/migrations/0001_01_01_000001_create_cache_table.php',
            'database/migrations/0001_01_01_000002_create_jobs_table.php',
            'tests/TestCase.php',
            'tests/Feature/ExampleTest.php',
            'tests/Unit/ExampleTest.php',
        ] as $file) {
            $this->assertFileExists($this->outputDir.'/'.$file);
        }

        $this->assertFileExists($this->outputDir.'/app/Models/Product.php');
        $this->assertFileExists($this->outputDir.'/app/Models/Category.php');
        $this->assertFileExists($this->outputDir.'/app/Models/Tag.php');
        $this->assertFileExists($this->outputDir.'/app/Http/Controllers/ProductController.php');
        $this->assertFileExists($this->outputDir.'/resources/views/products/index.blade.php');
        $this->assertNotEmpty(glob($this->outputDir.'/database/migrations/*_create_products_table.php'));
        $this->assertNotEmpty(glob($this->outputDir.'/database/migrations/*_create_product_tag_table.php'));

        $productModel = File::get($this->outputDir.'/app/Models/Product.php');
        $this->assertStringContainsString('return $this->belongsTo(Category::class);', $productModel);
        $this->assertStringContainsString('return $this->belongsToMany(Tag::class)->withTimestamps();', $productModel);

        $tagModel = File::get($this->outputDir.'/app/Models/Tag.php');
        $this->assertStringContainsString('return $this->belongsToMany(Product::class)->withTimestamps();', $tagModel);

        $productMigration = File::get(glob($this->outputDir.'/database/migrations/*_create_products_table.php')[0]);
        $this->assertStringContainsString("\$table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();", $productMigration);

        $pivotMigration = File::get(glob($this->outputDir.'/database/migrations/*_create_product_tag_table.php')[0]);
        $this->assertStringContainsString("Schema::create('product_tag'", $pivotMigration);
        $this->assertStringContainsString("\$table->foreignId('product_id')->constrained('products')->cascadeOnDelete();", $pivotMigration);
        $this->assertStringContainsString("\$table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();", $pivotMigration);

        $productController = File::get($this->outputDir.'/app/Http/Controllers/ProductController.php');
        $this->assertStringContainsString("'tags' => 'nullable|array'", $productController);
        $this->assertStringContainsString('$product->tags()->sync($relations[\'tags\'] ?? []);', $productController);

        $readme = File::get($this->outputDir.'/README.md');
        $this->assertStringContainsString('This is a complete Laravel application', $readme);
        $this->assertStringContainsString('composer install', $readme);
        $this->assertStringContainsString('npm install', $readme);
        $this->assertStringContainsString('CREATE DATABASE inventorydemo', $readme);
        $this->assertStringContainsString('php artisan migrate', $readme);
        $this->assertStringNotContainsString('database.sqlite', $readme);
        $this->assertStringNotContainsString('Generisani entiteti', $readme);

        $webRoutes = File::get($this->outputDir.'/routes/web.php');
        $this->assertStringContainsString("Route::middleware('auth')->group", $webRoutes);
        $this->assertStringContainsString("require __DIR__.'/auth.php';", $webRoutes);

        $authRoutes = File::get($this->outputDir.'/routes/auth.php');
        $this->assertStringContainsString("Route::get('register'", $authRoutes);
        $this->assertStringContainsString("Route::get('login'", $authRoutes);
        $this->assertStringContainsString("Route::post('logout'", $authRoutes);

        $env = File::get($this->outputDir.'/.env.example');
        $this->assertStringContainsString('APP_NAME=InventoryDemo', $env);
        $this->assertStringContainsString('DB_CONNECTION=mysql', $env);
        $this->assertStringContainsString('DB_DATABASE=inventorydemo', $env);

        $composer = json_decode(File::get($this->outputDir.'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('^8.4', $composer['require']['php']);
        $this->assertNotContains('@php -r "file_exists(\'database/database.sqlite\') || touch(\'database/database.sqlite\');"', $composer['scripts']['setup']);

        $phpunit = File::get($this->outputDir.'/phpunit.xml');
        $this->assertStringContainsString('name="DB_CONNECTION" value="mysql"', $phpunit);
        $this->assertStringContainsString('name="DB_DATABASE" value="inventorydemo_test"', $phpunit);
    }

    public function test_it_generates_type_specific_field_handling(): void
    {
        (new LaravelProjectGenerator())->generate($this->fieldSpecification(), $this->outputDir);

        $accountModel = File::get($this->outputDir.'/app/Models/Account.php');
        $this->assertStringContainsString("'is_active' => 'boolean'", $accountModel);
        $this->assertStringContainsString("'published_on' => 'date'", $accountModel);
        $this->assertStringContainsString("'published_at' => 'datetime'", $accountModel);
        $this->assertStringContainsString("'balance' => 'decimal:2'", $accountModel);
        $this->assertStringContainsString("'password' => 'hashed'", $accountModel);
        $this->assertStringContainsString("protected \$hidden = [\n        'password',\n    ];", $accountModel);

        $accountMigration = File::get(glob($this->outputDir.'/database/migrations/*_create_accounts_table.php')[0]);
        $this->assertStringContainsString("\$table->boolean('is_active');", $accountMigration);
        $this->assertStringContainsString("\$table->date('published_on')->nullable();", $accountMigration);
        $this->assertStringContainsString("\$table->dateTime('published_at')->nullable();", $accountMigration);
        $this->assertStringContainsString("\$table->decimal('balance', 10, 2);", $accountMigration);

        $accountController = File::get($this->outputDir.'/app/Http/Controllers/AccountController.php');
        $this->assertStringContainsString("'password' => 'required|string|min:8'", $accountController);
        $this->assertStringContainsString("\$rules['password'] = str_replace('required|', 'nullable|', \$rules['password']);", $accountController);
        $this->assertStringContainsString("foreach (['password'] as \$passwordField)", $accountController);

        $createView = File::get($this->outputDir.'/resources/views/accounts/create.blade.php');
        $this->assertStringContainsString('<select name="is_active">', $createView);
        $this->assertStringContainsString('<option value="1" @selected((string) old(\'is_active\') === \'1\')>Yes</option>', $createView);
        $this->assertStringContainsString('<input type="datetime-local" name="published_at"', $createView);
        $this->assertStringContainsString('<input type="password" name="password" autocomplete="new-password" value="{{ old(\'password\') }}">', $createView);

        $editView = File::get($this->outputDir.'/resources/views/accounts/edit.blade.php');
        $this->assertStringContainsString('<input type="password" name="password" autocomplete="new-password">', $editView);
        $this->assertStringContainsString("old('published_at', optional(\$account->published_at)->format('Y-m-d\\TH:i'))", $editView);

        $showView = File::get($this->outputDir.'/resources/views/accounts/show.blade.php');
        $this->assertStringContainsString("\$account->is_active === null ? '-' : (\$account->is_active ? 'Yes' : 'No')", $showView);
        $this->assertStringContainsString("\$account->password ? 'Set' : '-'", $showView);
    }

    private function specification(): array
    {
        return [
            'app' => 'InventoryDemo',
            'entities' => [
                [
                    'name' => 'Category',
                    'table' => 'categories',
                    'route' => 'categories',
                    'variable' => 'category',
                    'collection' => 'categories',
                    'fields' => [
                        [
                            'name' => 'name',
                            'label' => 'Name',
                            'type' => 'string',
                            'required' => true,
                            'unique' => false,
                        ],
                    ],
                    'relations' => [
                        [
                            'type' => 'hasMany',
                            'source' => 'Category',
                            'target' => 'Product',
                            'method' => 'products',
                            'foreign_key' => null,
                            'target_table' => 'products',
                            'target_variable' => 'product',
                            'target_collection' => 'products',
                            'pivot_table' => null,
                            'pivot_models' => [],
                            'inferred' => false,
                        ],
                    ],
                ],
                [
                    'name' => 'Product',
                    'table' => 'products',
                    'route' => 'products',
                    'variable' => 'product',
                    'collection' => 'products',
                    'fields' => [
                        [
                            'name' => 'name',
                            'label' => 'Name',
                            'type' => 'string',
                            'required' => true,
                            'unique' => false,
                        ],
                    ],
                    'relations' => [
                        [
                            'type' => 'belongsTo',
                            'source' => 'Product',
                            'target' => 'Category',
                            'method' => 'category',
                            'foreign_key' => 'category_id',
                            'target_table' => 'categories',
                            'target_variable' => 'category',
                            'target_collection' => 'categories',
                            'pivot_table' => null,
                            'pivot_models' => [],
                            'inferred' => true,
                        ],
                        [
                            'type' => 'belongsToMany',
                            'source' => 'Product',
                            'target' => 'Tag',
                            'method' => 'tags',
                            'foreign_key' => null,
                            'target_table' => 'tags',
                            'target_variable' => 'tag',
                            'target_collection' => 'tags',
                            'pivot_table' => 'product_tag',
                            'pivot_models' => ['Product', 'Tag'],
                            'inferred' => false,
                        ],
                    ],
                ],
                [
                    'name' => 'Tag',
                    'table' => 'tags',
                    'route' => 'tags',
                    'variable' => 'tag',
                    'collection' => 'tags',
                    'fields' => [
                        [
                            'name' => 'name',
                            'label' => 'Name',
                            'type' => 'string',
                            'required' => true,
                            'unique' => false,
                        ],
                    ],
                    'relations' => [
                        [
                            'type' => 'belongsToMany',
                            'source' => 'Tag',
                            'target' => 'Product',
                            'method' => 'products',
                            'foreign_key' => null,
                            'target_table' => 'products',
                            'target_variable' => 'product',
                            'target_collection' => 'products',
                            'pivot_table' => 'product_tag',
                            'pivot_models' => ['Product', 'Tag'],
                            'inferred' => true,
                        ],
                    ],
                ],
            ],
        ];
    }

    private function fieldSpecification(): array
    {
        return [
            'app' => 'FieldDemo',
            'entities' => [
                [
                    'name' => 'Account',
                    'table' => 'accounts',
                    'route' => 'accounts',
                    'variable' => 'account',
                    'collection' => 'accounts',
                    'fields' => [
                        [
                            'name' => 'email',
                            'label' => 'Email',
                            'type' => 'email',
                            'required' => true,
                            'unique' => true,
                        ],
                        [
                            'name' => 'password',
                            'label' => 'Password',
                            'type' => 'password',
                            'required' => true,
                            'unique' => false,
                        ],
                        [
                            'name' => 'is_active',
                            'label' => 'Is Active',
                            'type' => 'boolean',
                            'required' => true,
                            'unique' => false,
                        ],
                        [
                            'name' => 'published_on',
                            'label' => 'Published On',
                            'type' => 'date',
                            'required' => false,
                            'unique' => false,
                        ],
                        [
                            'name' => 'published_at',
                            'label' => 'Published At',
                            'type' => 'datetime',
                            'required' => false,
                            'unique' => false,
                        ],
                        [
                            'name' => 'balance',
                            'label' => 'Balance',
                            'type' => 'decimal',
                            'required' => true,
                            'unique' => false,
                        ],
                    ],
                    'relations' => [],
                ],
            ],
        ];
    }
}
