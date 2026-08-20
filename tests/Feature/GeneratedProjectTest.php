<?php

namespace Tests\Feature;

use App\Jobs\GenerateLaravelProject;
use App\Models\GeneratedEntity;
use App\Models\GeneratedProject;
use App\Models\GeneratedRelation;
use App\Models\User;
use App\Services\Dsl\DslParser;
use App\Services\Generation\LaravelProjectGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;
use ZipArchive;

class GeneratedProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_queue_project_generation(): void
    {
        Bus::fake();

        $user = $this->createUser();

        $response = $this
            ->actingAs($user)
            ->post(route('generator.store'), [
                'name' => 'Inventory Demo',
                'dsl' => <<<'DSL'
app InventoryDemo {
  entity Category {
    title: string required
    hasMany Product
  }

  entity Product {
    name: string required
    price: decimal required
    belongsTo Category
  }
}
DSL,
            ]);

        $project = GeneratedProject::query()->firstOrFail();

        $response->assertRedirect(route('generator.show', $project));

        $this->assertSame($user->id, $project->user_id);
        $this->assertSame('Inventory Demo', $project->name);
        $this->assertSame('queued', $project->status);
        $this->assertNotNull($project->uuid);
        $this->assertNotNull($project->dsl_path);
        $this->assertFileExists(storage_path('app/'.$project->dsl_path));
        $this->assertSame('belongsTo', GeneratedRelation::query()->where('target', 'Category')->firstOrFail()->type);

        Bus::assertDispatchedSync(GenerateLaravelProject::class, fn (GenerateLaravelProject $job) => $job->projectId === $project->id);
    }

    public function test_guests_cannot_access_generator_form(): void
    {
        $this->get(route('generator.create'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_their_generated_projects(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();

        GeneratedProject::query()->create([
            'user_id' => $user->id,
            'uuid' => '11111111-1111-1111-1111-111111111111',
            'name' => 'Visible Project',
            'status' => 'succeeded',
        ]);

        GeneratedProject::query()->create([
            'user_id' => $otherUser->id,
            'uuid' => '22222222-2222-2222-2222-222222222222',
            'name' => 'Hidden Project',
            'status' => 'succeeded',
        ]);

        $this->actingAs($user)
            ->get(route('generator.index'))
            ->assertOk()
            ->assertSee('Visible Project')
            ->assertDontSee('Hidden Project');
    }

    public function test_authenticated_user_can_open_project_for_editing(): void
    {
        $user = $this->createUser();
        $project = GeneratedProject::query()->create([
            'user_id' => $user->id,
            'uuid' => '33333333-3333-3333-3333-333333333333',
            'name' => 'Inventory Demo',
            'status' => 'succeeded',
        ]);

        $entity = $project->entities()->create(['name' => 'Product']);
        $entity->fields()->create([
            'name' => 'price',
            'type' => 'decimal',
            'is_required' => true,
            'is_unique' => false,
        ]);

        $this->actingAs($user)
            ->get(route('generator.edit', $project))
            ->assertOk()
            ->assertSee('Inventory Demo')
            ->assertSee('Product');
    }

    public function test_authenticated_user_can_update_project_specification(): void
    {
        Bus::fake();

        $user = $this->createUser();
        $project = GeneratedProject::query()->create([
            'user_id' => $user->id,
            'uuid' => '44444444-4444-4444-4444-444444444444',
            'name' => 'Old Demo',
            'status' => 'succeeded',
            'zip_path' => 'generator/old/project.zip',
        ]);

        $entity = $project->entities()->create(['name' => 'OldModel']);
        $entity->fields()->create([
            'name' => 'title',
            'type' => 'string',
            'is_required' => true,
            'is_unique' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('generator.update', $project), [
                'name' => 'Updated Demo',
                'dsl' => <<<'DSL'
app UpdatedDemo {
  entity Product {
    name: string required
    sku: string required unique
  }
}
DSL,
            ]);

        $response->assertRedirect(route('generator.show', $project));

        $project->refresh();
        $this->assertSame('Updated Demo', $project->name);
        $this->assertSame('queued', $project->status);
        $this->assertNull($project->zip_path);
        $this->assertSame(['Product'], $project->entities()->pluck('name')->all());
        $this->assertSame(['name', 'sku'], GeneratedEntity::query()->where('name', 'Product')->firstOrFail()->fields()->pluck('name')->all());

        Bus::assertDispatchedSync(GenerateLaravelProject::class, fn (GenerateLaravelProject $job) => $job->projectId === $project->id);
    }

    public function test_project_persistence_stores_only_direct_relationships(): void
    {
        Bus::fake();

        $user = $this->createUser();

        $this
            ->actingAs($user)
            ->post(route('generator.store'), [
                'name' => 'Blog Demo',
                'dsl' => <<<'DSL'
app BlogDemo {
  entity User {
    name: string required
    hasMany Post
  }

  entity Post {
    title: string required
  }
}
DSL,
            ]);

        $this->assertSame(1, GeneratedRelation::query()->count());
        $this->assertSame('hasMany', GeneratedRelation::query()->firstOrFail()->type);
        $this->assertSame('Post', GeneratedRelation::query()->firstOrFail()->target);
    }

    public function test_authenticated_user_can_rerun_failed_project_generation(): void
    {
        Bus::fake();

        $user = $this->createUser();
        $project = GeneratedProject::query()->create([
            'user_id' => $user->id,
            'uuid' => '55555555-5555-5555-5555-555555555555',
            'name' => 'Failed Demo',
            'status' => 'failed',
            'error_message' => 'Previous failure',
            'output_path' => 'generator/failed/out',
            'zip_path' => 'generator/failed/project.zip',
        ]);

        $workDir = storage_path('app/generator/'.$project->uuid.'/input');
        if (!is_dir($workDir)) {
            mkdir($workDir, 0775, true);
        }
        file_put_contents($workDir.'/model.mydsl', <<<'DSL'
app FailedDemo {
  entity Product {
    name: string required
  }
}
DSL);

        $project->forceFill([
            'dsl_path' => 'generator/'.$project->uuid.'/input/model.mydsl',
        ])->save();

        $response = $this
            ->actingAs($user)
            ->post(route('generator.rerun', $project));

        $response->assertRedirect(route('generator.show', $project));

        $project->refresh();
        $this->assertSame('queued', $project->status);
        $this->assertNull($project->error_message);
        $this->assertNull($project->output_path);
        $this->assertNull($project->zip_path);

        Bus::assertDispatchedSync(GenerateLaravelProject::class, fn (GenerateLaravelProject $job) => $job->projectId === $project->id);
    }

    public function test_generation_zip_contains_only_project_files_from_out_directory(): void
    {
        $user = $this->createUser();
        $project = GeneratedProject::query()->create([
            'user_id' => $user->id,
            'uuid' => '77777777-7777-7777-7777-777777777777',
            'name' => 'Shop Demo',
            'status' => 'queued',
        ]);

        $inputDir = storage_path('app/generator/'.$project->uuid.'/input');
        if (!is_dir($inputDir)) {
            mkdir($inputDir, 0775, true);
        }

        file_put_contents($inputDir.'/model.mydsl', <<<'DSL'
app ShopDemo {
  entity Product {
    name: string required
  }
}
DSL);

        (new GenerateLaravelProject($project->id))->handle(
            app(DslParser::class),
            app(LaravelProjectGenerator::class),
        );

        $project->refresh();

        $this->assertSame('succeeded', $project->status);
        $this->assertSame('generator/'.$project->uuid.'/out', $project->output_path);
        $this->assertSame('generator/'.$project->uuid.'/shop-demo.zip', $project->zip_path);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open(storage_path('app/'.$project->zip_path)));

        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entries[] = $zip->getNameIndex($i);
        }
        $zip->close();

        $this->assertContains('artisan', $entries);
        $this->assertContains('app/Models/Product.php', $entries);
        $this->assertContains('database/migrations/0001_01_01_000000_create_users_table.php', $entries);
        $this->assertFalse(collect($entries)->contains(fn (string $entry): bool => str_starts_with($entry, 'home/')));
        $this->assertFalse(collect($entries)->contains(fn (string $entry): bool => str_contains($entry, '/storage/app/generator/')));
    }

    public function test_authenticated_user_can_download_project_with_absolute_zip_path(): void
    {
        $user = $this->createUser();
        $project = GeneratedProject::query()->create([
            'user_id' => $user->id,
            'uuid' => '66666666-6666-6666-6666-666666666666',
            'name' => 'Absolute Demo',
            'status' => 'succeeded',
        ]);

        $zipPath = storage_path('app/generator/'.$project->uuid.'/project.zip');
        if (!is_dir(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0775, true);
        }
        file_put_contents($zipPath, 'zip-content');

        $project->forceFill([
            'zip_path' => $zipPath,
        ])->save();

        $this
            ->actingAs($user)
            ->get(route('generator.download', $project))
            ->assertOk()
            ->assertDownload('absolute-demo.zip');
    }
}
