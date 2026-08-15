<?php

namespace Tests\Feature;

use App\Jobs\GenerateLaravelProject;
use App\Models\GeneratedProject;
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
  entity Product {
    name: string required
    price: decimal required
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

        Bus::assertDispatched(GenerateLaravelProject::class, fn (GenerateLaravelProject $job) => $job->projectId === $project->id);
    }

    public function test_guests_cannot_access_generator_form(): void
    {
        $this->get(route('generator.create'))
            ->assertRedirect(route('login'));
    }
}
