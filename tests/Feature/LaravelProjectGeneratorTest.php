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
            'database/seeders/DatabaseSeeder.php',
            'database/seeders/UserSeeder.php',
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
        $this->assertNotEmpty(glob($this->outputDir.'/database/migrations/*_add_foreign_keys_to_products_table.php'));
        $this->assertNotEmpty(glob($this->outputDir.'/database/migrations/*_create_product_tag_table.php'));

        $productModel = File::get($this->outputDir.'/app/Models/Product.php');
        $this->assertStringContainsString('return $this->belongsTo(Category::class);', $productModel);
        $this->assertStringContainsString('return $this->belongsToMany(Tag::class)->withTimestamps();', $productModel);

        $tagModel = File::get($this->outputDir.'/app/Models/Tag.php');
        $this->assertStringContainsString('return $this->belongsToMany(Product::class)->withTimestamps();', $tagModel);

        $productMigration = File::get(glob($this->outputDir.'/database/migrations/*_create_products_table.php')[0]);
        $this->assertStringContainsString("\$table->foreignId('category_id')->nullable();", $productMigration);
        $this->assertStringNotContainsString("->constrained('categories')", $productMigration);

        $productForeignKeysMigration = File::get(glob($this->outputDir.'/database/migrations/*_add_foreign_keys_to_products_table.php')[0]);
        $this->assertStringContainsString("\$table->foreign('category_id')->references('id')->on('categories')->cascadeOnDelete();", $productForeignKeysMigration);
        $this->assertStringContainsString("\$table->dropForeign(['category_id']);", $productForeignKeysMigration);

        $pivotMigration = File::get(glob($this->outputDir.'/database/migrations/*_create_product_tag_table.php')[0]);
        $this->assertStringContainsString("Schema::create('product_tag'", $pivotMigration);
        $this->assertStringContainsString("\$table->foreignId('product_id')->constrained('products')->cascadeOnDelete();", $pivotMigration);
        $this->assertStringContainsString("\$table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();", $pivotMigration);

        $productController = File::get($this->outputDir.'/app/Http/Controllers/ProductController.php');
        $this->assertStringContainsString("'tags' => 'nullable|array'", $productController);
        $this->assertStringContainsString('$product->tags()->sync($relations[\'tags\'] ?? []);', $productController);
        $this->assertStringContainsString("return redirect()->route('products.index')", $productController);
        $this->assertStringContainsString("->with('success', 'Product created successfully.')", $productController);
        $this->assertStringContainsString('$request->validate($rules, $this->validationMessages())', $productController);
        $this->assertStringContainsString("'category_id' => 'nullable|integer|exists:categories,id'", $productController);

        $databaseSeeder = File::get($this->outputDir.'/database/seeders/DatabaseSeeder.php');
        $this->assertStringContainsString('UserSeeder::class', $databaseSeeder);

        $userSeeder = File::get($this->outputDir.'/database/seeders/UserSeeder.php');
        $this->assertStringContainsString("'email' => 'test@example.com'", $userSeeder);
        $this->assertStringContainsString("'password' => Hash::make('password')", $userSeeder);

        $readme = File::get($this->outputDir.'/README.md');
        $this->assertStringContainsString('This is a complete Laravel application', $readme);
        $this->assertStringContainsString('composer install', $readme);
        $this->assertStringContainsString('npm install', $readme);
        $this->assertStringContainsString('CREATE DATABASE inventorydemo', $readme);
        $this->assertStringContainsString('php artisan migrate', $readme);
        $this->assertStringContainsString('php artisan db:seed', $readme);
        $this->assertStringContainsString('test@example.com', $readme);
        $this->assertStringContainsString('php artisan storage:link', $readme);
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
        $this->assertContains('@php artisan db:seed --force', $composer['scripts']['setup']);
        $this->assertNotContains('@php -r "file_exists(\'database/database.sqlite\') || touch(\'database/database.sqlite\');"', $composer['scripts']['setup']);

        $phpunit = File::get($this->outputDir.'/phpunit.xml');
        $this->assertStringContainsString('name="DB_CONNECTION" value="mysql"', $phpunit);
        $this->assertStringContainsString('name="DB_DATABASE" value="inventorydemo_test"', $phpunit);

        $dashboard = File::get($this->outputDir.'/resources/views/dashboard.blade.php');
        $this->assertStringContainsString('What this app includes', $dashboard);
        $this->assertStringNotContainsString('Generated CRUD', $dashboard);

        $css = File::get($this->outputDir.'/resources/css/app.css');
        $this->assertStringContainsString('.form-card', $css);
        $this->assertStringNotContainsString('max-w-3xl', $css);
        $this->assertStringContainsString('.confirm-dialog-backdrop', $css);

        $confirmDialog = File::get($this->outputDir.'/resources/views/components/ui/confirm-dialog.blade.php');
        $this->assertStringContainsString('confirm-dialog-panel', $confirmDialog);
        $this->assertStringNotContainsString('return confirm', $confirmDialog);

        $pagination = File::get($this->outputDir.'/resources/views/components/ui/pagination.blade.php');
        $this->assertStringContainsString('pagination-pages', $pagination);
        $this->assertStringContainsString('Showing {{ $paginator->firstItem() }}', $pagination);
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
        $this->assertStringContainsString('<input id="field_is_active" type="checkbox" name="is_active"', $createView);
        $this->assertStringContainsString('<input id="field_published_at" type="datetime-local" name="published_at"', $createView);
        $this->assertStringContainsString('<input id="field_password" type="password" name="password" autocomplete="new-password" value="{{ old(\'password\') }}"', $createView);
        $this->assertStringNotContainsString('optional</span>', $createView);

        $editView = File::get($this->outputDir.'/resources/views/accounts/edit.blade.php');
        $this->assertStringContainsString('<input id="field_password" type="password" name="password" autocomplete="new-password"', $editView);
        $this->assertStringContainsString("old('published_at', optional(\$account->published_at)->format('Y-m-d\\TH:i'))", $editView);

        $showView = File::get($this->outputDir.'/resources/views/accounts/show.blade.php');
        $this->assertStringContainsString("\$account->is_active === null ? '<span class=\"badge muted\">Not set</span>'", $showView);
        $this->assertStringContainsString("<span class=\"badge success\">Yes</span>", $showView);
        $this->assertStringContainsString("\$account->password ? 'Set' : '-'", $showView);
    }

    public function test_it_extends_the_default_laravel_users_table_for_user_entities(): void
    {
        (new LaravelProjectGenerator())->generate([
            'app' => 'ShopDemo',
            'entities' => [
                [
                    'name' => 'User',
                    'table' => 'users',
                    'route' => 'users',
                    'variable' => 'user',
                    'collection' => 'users',
                    'fields' => [
                        [
                            'name' => 'username',
                            'label' => 'Username',
                            'type' => 'string',
                            'required' => true,
                            'unique' => true,
                        ],
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
                            'name' => 'role',
                            'label' => 'Role',
                            'type' => 'string',
                            'required' => false,
                            'unique' => false,
                        ],
                        [
                            'name' => 'id',
                            'label' => 'Id',
                            'type' => 'string',
                            'required' => true,
                            'unique' => false,
                        ],
                    ],
                    'relations' => [],
                ],
            ],
        ], $this->outputDir);

        $createUserMigrations = array_values(array_filter(
            glob($this->outputDir.'/database/migrations/*_create_users_table.php') ?: [],
            fn (string $path): bool => basename($path) !== '0001_01_01_000000_create_users_table.php',
        ));

        $this->assertSame([], $createUserMigrations);
        $this->assertFileExists($this->outputDir.'/database/migrations/0001_01_01_000000_create_users_table.php');

        $updateUserMigration = File::get(glob($this->outputDir.'/database/migrations/*_update_users_table.php')[0]);
        $this->assertStringContainsString("Schema::table('users'", $updateUserMigration);
        $this->assertStringContainsString("\$table->string('username')->unique();", $updateUserMigration);
        $this->assertStringContainsString("\$table->string('role')->nullable();", $updateUserMigration);
        $this->assertStringNotContainsString("\$table->id();", $updateUserMigration);
        $this->assertStringNotContainsString("\$table->string('email'", $updateUserMigration);
        $this->assertStringNotContainsString("\$table->string('password'", $updateUserMigration);

        $userModel = File::get($this->outputDir.'/app/Models/User.php');
        $this->assertStringContainsString('class User extends Authenticatable', $userModel);
        $this->assertStringContainsString("#[Fillable(['name', 'email', 'password', 'username', 'role'])]", $userModel);
        $this->assertStringContainsString("'password' => 'hashed'", $userModel);

        $registerView = File::get($this->outputDir.'/resources/views/auth/register.blade.php');
        $this->assertStringContainsString('name="username"', $registerView);
        $this->assertStringContainsString('name="role"', $registerView);

        $registeredController = File::get($this->outputDir.'/app/Http/Controllers/Auth/RegisteredUserController.php');
        $this->assertStringContainsString("'username' => 'required|string|unique:users,username'", $registeredController);
        $this->assertStringContainsString("'role' => 'nullable|string'", $registeredController);
        $this->assertStringContainsString("'username' => \$validated['username'] ?? null", $registeredController);

        $userSeeder = File::get($this->outputDir.'/database/seeders/UserSeeder.php');
        $this->assertStringContainsString("'username' => 'testuser'", $userSeeder);
        $this->assertStringContainsString("'role' => 'Sample Role'", $userSeeder);
    }

    public function test_it_generates_custom_pivot_table_for_many_to_many_relationships(): void
    {
        (new LaravelProjectGenerator())->generate($this->customPivotSpecification(), $this->outputDir);

        $productModel = File::get($this->outputDir.'/app/Models/Product.php');
        $tagModel = File::get($this->outputDir.'/app/Models/Tag.php');

        $this->assertStringContainsString("return \$this->belongsToMany(Tag::class, 'catalog_labels')->withTimestamps();", $productModel);
        $this->assertStringContainsString("return \$this->belongsToMany(Product::class, 'catalog_labels')->withTimestamps();", $tagModel);

        $pivotMigration = File::get(glob($this->outputDir.'/database/migrations/*_create_catalog_labels_table.php')[0]);
        $this->assertStringContainsString("Schema::create('catalog_labels'", $pivotMigration);
        $this->assertStringContainsString("\$table->foreignId('product_id')->constrained('products')->cascadeOnDelete();", $pivotMigration);
        $this->assertStringContainsString("\$table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();", $pivotMigration);
    }

    public function test_it_generates_relationship_ui_and_display_name_priority(): void
    {
        (new LaravelProjectGenerator())->generate($this->specification(), $this->outputDir);

        $productModel = File::get($this->outputDir.'/app/Models/Product.php');
        $this->assertStringContainsString("'name',", $productModel);
        $this->assertStringContainsString('public function displayName(): string', $productModel);

        $productIndex = File::get($this->outputDir.'/resources/views/products/index.blade.php');
        $this->assertStringContainsString('<x-ui.confirm-dialog', $productIndex);
        $this->assertStringContainsString('<x-ui.pagination :paginator="$products" />', $productIndex);
        $this->assertStringContainsString('class="action-list"', $productIndex);
        $this->assertStringNotContainsString('return confirm', $productIndex);

        $productCreate = File::get($this->outputDir.'/resources/views/products/create.blade.php');
        $this->assertStringContainsString('<label for="field_category_id">Category', $productCreate);
        $this->assertStringContainsString('<select id="field_category_id" name="category_id"', $productCreate);
        $this->assertStringContainsString('<fieldset class="field-full">', $productCreate);
        $this->assertStringContainsString('name="tags[]"', $productCreate);
        $this->assertStringContainsString('No Tag records yet. Save this record now and attach them later.', $productCreate);
        $this->assertStringNotContainsString('optional</span>', $productCreate);

        $categoryShow = File::get($this->outputDir.'/resources/views/categories/show.blade.php');
        $this->assertStringContainsString('class="detail-header"', $categoryShow);
        $this->assertStringContainsString('class="detail-breadcrumbs"', $categoryShow);
        $this->assertStringContainsString('<a class="button primary"', $categoryShow);
        $this->assertStringContainsString('<x-ui.table>', $categoryShow);
        $this->assertStringContainsString('<x-ui.confirm-dialog', $categoryShow);
        $this->assertStringContainsString('class="action-list"', $categoryShow);
        $this->assertStringNotContainsString('return confirm', $categoryShow);
        $this->assertStringNotContainsString('danger-zone', $categoryShow);
        $this->assertStringContainsString('No related Product records', $categoryShow);
        $this->assertStringContainsString("Route::has('products.create')", $categoryShow);
    }

    public function test_it_uses_display_field_first_for_generated_display_names(): void
    {
        (new LaravelProjectGenerator())->generate([
            'app' => 'DisplayDemo',
            'entities' => [
                [
                    'name' => 'Product',
                    'table' => 'products',
                    'route' => 'products',
                    'variable' => 'product',
                    'collection' => 'products',
                    'display_field' => 'sku',
                    'fields' => [
                        [
                            'name' => 'name',
                            'label' => 'Name',
                            'type' => 'string',
                            'required' => true,
                            'unique' => false,
                        ],
                        [
                            'name' => 'sku',
                            'label' => 'Sku',
                            'type' => 'string',
                            'required' => true,
                            'unique' => true,
                        ],
                    ],
                    'relations' => [],
                ],
            ],
        ], $this->outputDir);

        $productModel = File::get($this->outputDir.'/app/Models/Product.php');
        $this->assertStringContainsString("            'sku',\n            'name',", $productModel);
    }

    public function test_it_generates_image_file_uploads_and_metadata_validation(): void
    {
        (new LaravelProjectGenerator())->generate([
            'app' => 'MediaDemo',
            'entities' => [
                [
                    'name' => 'Asset',
                    'table' => 'assets',
                    'route' => 'assets',
                    'variable' => 'asset',
                    'collection' => 'assets',
                    'fields' => [
                        [
                            'name' => 'title',
                            'label' => 'Title',
                            'type' => 'string',
                            'required' => true,
                            'unique' => false,
                            'metadata' => ['minLength' => 3, 'maxLength' => 120],
                        ],
                        [
                            'name' => 'photo',
                            'label' => 'Photo',
                            'type' => 'image',
                            'required' => false,
                            'unique' => false,
                            'metadata' => ['accept' => 'image/png,image/jpeg', 'max' => 2048],
                        ],
                    ],
                    'relations' => [],
                ],
            ],
        ], $this->outputDir);

        $assetModel = File::get($this->outputDir.'/app/Models/Asset.php');
        $this->assertStringContainsString("'title',", $assetModel);

        $assetController = File::get($this->outputDir.'/app/Http/Controllers/AssetController.php');
        $this->assertStringContainsString("'title' => 'required|string|min:3|max:120'", $assetController);
        $this->assertStringContainsString("'photo' => 'nullable|image|max:2048|mimetypes:image/png,image/jpeg'", $assetController);
        $this->assertStringContainsString("\$request->file(\$fileField)->store(\$fileField, 'public')", $assetController);

        $assetCreate = File::get($this->outputDir.'/resources/views/assets/create.blade.php');
        $this->assertStringContainsString('enctype="multipart/form-data"', $assetCreate);
        $this->assertStringContainsString('<input id="field_photo" type="file" name="photo" accept="image/png,image/jpeg"', $assetCreate);

        $assetShow = File::get($this->outputDir.'/resources/views/assets/show.blade.php');
        $this->assertStringContainsString('class="image-thumb"', $assetShow);
    }

    public function test_it_does_not_generate_unique_indexes_for_non_indexable_or_nullable_fields(): void
    {
        (new LaravelProjectGenerator())->generate([
            'app' => 'InvalidUniqueDemo',
            'entities' => [
                [
                    'name' => 'Article',
                    'table' => 'articles',
                    'route' => 'articles',
                    'variable' => 'article',
                    'collection' => 'articles',
                    'fields' => [
                        [
                            'name' => 'summary',
                            'label' => 'Summary',
                            'type' => 'text',
                            'required' => true,
                            'unique' => true,
                        ],
                        [
                            'name' => 'reference',
                            'label' => 'Reference',
                            'type' => 'string',
                            'required' => false,
                            'unique' => true,
                        ],
                    ],
                    'relations' => [],
                ],
            ],
        ], $this->outputDir);

        $articleMigration = File::get(glob($this->outputDir.'/database/migrations/*_create_articles_table.php')[0]);
        $this->assertStringContainsString("\$table->text('summary');", $articleMigration);
        $this->assertStringContainsString("\$table->string('reference')->nullable();", $articleMigration);
        $this->assertStringNotContainsString("->unique()", $articleMigration);

        $articleController = File::get($this->outputDir.'/app/Http/Controllers/ArticleController.php');
        $this->assertStringContainsString("'summary' => 'required|string'", $articleController);
        $this->assertStringContainsString("'reference' => 'nullable|string'", $articleController);
        $this->assertStringNotContainsString('|unique:articles', $articleController);
    }

    public function test_it_generates_only_enabled_model_screens_and_actions(): void
    {
        (new LaravelProjectGenerator())->generate([
            'app' => 'ScreenOptionsDemo',
            'entities' => [
                [
                    'name' => 'Product',
                    'table' => 'products',
                    'route' => 'products',
                    'variable' => 'product',
                    'collection' => 'products',
                    'features' => [
                        'index' => true,
                        'create' => false,
                        'edit' => false,
                        'show' => true,
                        'delete' => false,
                    ],
                    'fields' => [
                        [
                            'name' => 'name',
                            'label' => 'Name',
                            'type' => 'string',
                            'required' => true,
                            'unique' => false,
                        ],
                    ],
                    'relations' => [],
                ],
            ],
        ], $this->outputDir);

        $this->assertFileExists($this->outputDir.'/resources/views/products/index.blade.php');
        $this->assertFileExists($this->outputDir.'/resources/views/products/show.blade.php');
        $this->assertFileDoesNotExist($this->outputDir.'/resources/views/products/create.blade.php');
        $this->assertFileDoesNotExist($this->outputDir.'/resources/views/products/edit.blade.php');

        $routes = File::get($this->outputDir.'/routes/web.php');
        $this->assertStringContainsString("Route::resource('products', ProductController::class)->only(['index', 'show']);", $routes);

        $controller = File::get($this->outputDir.'/app/Http/Controllers/ProductController.php');
        $this->assertStringContainsString('public function index(): View', $controller);
        $this->assertStringContainsString('public function show(Product $product): View', $controller);
        $this->assertStringNotContainsString('public function create(): View', $controller);
        $this->assertStringNotContainsString('public function destroy(Product $product): RedirectResponse', $controller);

        $index = File::get($this->outputDir.'/resources/views/products/index.blade.php');
        $this->assertStringContainsString("route('products.show', \$product)", $index);
        $this->assertStringNotContainsString("route('products.create')", $index);
        $this->assertStringNotContainsString("route('products.edit', \$product)", $index);
        $this->assertStringNotContainsString("route('products.destroy', \$product)", $index);
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

    private function customPivotSpecification(): array
    {
        return [
            'app' => 'CatalogDemo',
            'entities' => [
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
                            'type' => 'belongsToMany',
                            'source' => 'Product',
                            'target' => 'Tag',
                            'method' => 'tags',
                            'foreign_key' => null,
                            'target_table' => 'tags',
                            'target_variable' => 'tag',
                            'target_collection' => 'tags',
                            'pivot_table' => 'catalog_labels',
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
                            'pivot_table' => 'catalog_labels',
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
