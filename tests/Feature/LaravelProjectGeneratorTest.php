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
        $this->assertFileExists($this->outputDir.'/app/Http/Controllers/ProductController.php');
        $this->assertFileExists($this->outputDir.'/resources/views/products/index.blade.php');
        $this->assertNotEmpty(glob($this->outputDir.'/database/migrations/*_create_products_table.php'));

        $readme = File::get($this->outputDir.'/README.md');
        $this->assertStringContainsString('This is a complete Laravel application', $readme);
        $this->assertStringContainsString('composer install', $readme);
        $this->assertStringContainsString('npm install', $readme);
        $this->assertStringContainsString('php artisan migrate', $readme);
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
        $this->assertStringContainsString('DB_CONNECTION=sqlite', $env);

        $composer = json_decode(File::get($this->outputDir.'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('^8.4', $composer['require']['php']);

        $phpunit = File::get($this->outputDir.'/phpunit.xml');
        $this->assertStringContainsString('name="DB_CONNECTION" value="sqlite"', $phpunit);
    }

    private function specification(): array
    {
        return [
            'app' => 'InventoryDemo',
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
                    'relations' => [],
                ],
            ],
        ];
    }
}
