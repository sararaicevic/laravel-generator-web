<?php

namespace Tests\Feature;

use App\Jobs\GenerateLaravelProject;
use App\Models\GeneratedEntity;
use App\Models\GeneratedProject;
use App\Models\GeneratedRelation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class GeneratedProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_queue_project_generation(): void
    {
        Bus::fake();

        $user = User::factory()->create();

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
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

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
        $user = User::factory()->create();
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

        $user = User::factory()->create();
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

    public function test_authenticated_user_can_rerun_failed_project_generation(): void
    {
        Bus::fake();

        $user = User::factory()->create();
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
}
