<?php

use App\Jobs\ProcessSourceJob;
use App\Models\Project;
use App\Models\Source;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebHooksSourceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testShowFormForURLSourceWebHook()
    {
        $user = User::factory()->withPersonalTeam()->create();

        $user = $this->createTeam($user);

        $project = Project::factory()->create([
            'team_id' => $user->current_team_id,
        ]);

        $response = $this->actingAs($user)->get(route('sources.web_hook.create', ['project' => $project->id]));

        $response->assertStatus(200);
    }

    public function testAllowToEditWebHook()
    {

        $user = User::factory()->withPersonalTeam()->create();

        $user = $this->createTeam($user);

        $project = Project::factory()->create([
            'team_id' => $user->current_team_id,
        ]);

        $source = Source::factory()->create([
            'project_id' => $project->id,
        ]);

        $response = $this->actingAs($user)->get(route('sources.web_hook.edit', ['project' => $project->id, 'source' => $source->id]));

        $response->assertStatus(200);
    }

    public function testRunWebHook()
    {
        Queue::fake();

        $user = User::factory()->withPersonalTeam()->create();

        $user = $this->createTeam($user);

        $project = Project::factory()->create([
            'team_id' => $user->current_team_id,
        ]);

        $source = Source::factory()->create([
            'project_id' => $project->id,
        ]);

        $response = $this->actingAs($user)->post(route('sources.web_hook.run', ['project' => $project->id, 'source' => $source->id]));

        $response->assertRedirect(route('projects.show', ['project' => $project->id]));

        Queue::assertPushed(ProcessSourceJob::class);
    }

    public function testUpdateWebHook()
    {

        $user = User::factory()->withPersonalTeam()->create();

        $user = $this->createTeam($user);

        $project = Project::factory()->create([
            'team_id' => $user->current_team_id,
        ]);

        $source = Source::factory()->create([
            'project_id' => $project->id,
        ]);

        $response = $this->actingAs($user)->put(route('sources.web_hook.update', ['project' => $project->id, 'source' => $source->id]), ['name' => 'Foo', 'meta_data' => ['url' => 'https://foo.bar'], 'description' => 'Bar']);

        $response->assertRedirect(route('projects.show', ['project' => $project->id]));

        $this->assertEquals('Foo', $source->refresh()->name);
    }

    public function testCreateWebHook()
    {
        $user = User::factory()->withPersonalTeam()->create();

        $user = $this->createTeam($user);

        $project = Project::factory()->create([
            'team_id' => $user->current_team_id,
        ]);

        $this->assertDatabaseCount('sources', 0);

        $response = $this->actingAs($user)->post(route('sources.web_hook.store', ['project' => $project->id]), ['name' => 'Foo', 'description' => 'Bar', 'meta_data' => ['url' => 'https://foo.bar']]);

        $response->assertRedirect(route('projects.show', ['project' => $project->id]));

        $this->assertDatabaseCount('sources', 1);
    }
}
