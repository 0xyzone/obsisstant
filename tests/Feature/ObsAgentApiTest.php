<?php

namespace Tests\Feature;

use App\Events\ObsCommandAcknowledged;
use App\Events\ObsStudioStateUpdated;
use App\Models\ObsCommandLog;
use App\Models\ObsStudio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ObsAgentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_obs_agent_endpoints_require_valid_bearer_token(): void
    {
        $this->getJson('/api/obs-agent/bootstrap')->assertUnauthorized();

        $this->withToken('invalid_token')
            ->getJson('/api/obs-agent/bootstrap')
            ->assertUnauthorized();
    }

    public function test_obs_agent_bootstrap_returns_studio_reverb_and_pending_commands(): void
    {
        $studio = ObsStudio::factory()->create();
        $log = ObsCommandLog::query()->create([
            'obs_studio_id' => $studio->id,
            'request_type' => 'SetCurrentProgramScene',
            'request_data' => ['sceneName' => 'Main Scene'],
            'status' => 'dispatched',
        ]);

        $response = $this->withToken($studio->agent_token)
            ->getJson('/api/obs-agent/bootstrap');

        $response->assertOk()
            ->assertJsonPath('studio.id', $studio->id)
            ->assertJsonPath('studio.slug', $studio->slug)
            ->assertJsonPath('reverb.channel', "private-obs.studio.{$studio->id}.commands")
            ->assertJsonPath('pending_commands.0.command_uuid', $log->command_uuid);
    }

    public function test_obs_agent_can_authorize_its_private_reverb_command_channel(): void
    {
        $studio = ObsStudio::factory()->create();

        $this->withToken($studio->agent_token)
            ->postJson('/api/obs-agent/broadcasting/auth', [
                'socket_id' => '12345.67890',
                'channel_name' => "private-obs.studio.{$studio->id}.commands",
            ])
            ->assertOk()
            ->assertJsonStructure(['auth']);

        $otherStudioId = $studio->id + 999;
        $this->withToken($studio->agent_token)
            ->postJson('/api/obs-agent/broadcasting/auth', [
                'socket_id' => '12345.67890',
                'channel_name' => "private-obs.studio.{$otherStudioId}.commands",
            ])
            ->assertForbidden();
    }

    public function test_obs_agent_can_push_live_state_and_broadcast_update(): void
    {
        Event::fake([ObsStudioStateUpdated::class]);
        $studio = ObsStudio::factory()->create();

        $response = $this->withToken($studio->agent_token)
            ->postJson('/api/obs-agent/state', [
                'live_state' => [
                    'currentProgramScene' => 'Podcast Desk',
                    'outputs' => [
                        'streaming' => true,
                        'recording' => false,
                    ],
                    'scenes' => [
                        ['sceneName' => 'Podcast Desk', 'sceneIndex' => 0],
                    ],
                ],
            ]);

        $response->assertOk()->assertJsonPath('status', 'streaming');

        $studio->refresh();
        $this->assertSame('streaming', $studio->status);
        $this->assertSame('Podcast Desk', $studio->getCurrentProgramScene());
        $this->assertNotNull($studio->last_seen_at);

        Event::assertDispatched(ObsStudioStateUpdated::class);
    }

    public function test_obs_agent_can_acknowledge_commands(): void
    {
        Event::fake([ObsCommandAcknowledged::class]);
        $studio = ObsStudio::factory()->create();
        $log = ObsCommandLog::query()->create([
            'obs_studio_id' => $studio->id,
            'request_type' => 'StartStream',
            'request_data' => [],
            'status' => 'dispatched',
        ]);

        $this->withToken($studio->agent_token)
            ->postJson("/api/obs-agent/commands/{$log->command_uuid}/ack", [
                'status' => 'succeeded',
                'response_data' => ['ok' => true],
            ])
            ->assertOk();

        $log->refresh();
        $this->assertSame('succeeded', $log->status);
        $this->assertNotNull($log->executed_at);
        Event::assertDispatched(ObsCommandAcknowledged::class);
    }
}
