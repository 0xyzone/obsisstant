<?php

namespace Tests\Feature;

use App\Events\ObsCommandAcknowledged;
use App\Events\ObsRelayCatalogUpdated;
use App\Events\ObsStudioStateUpdated;
use App\Models\ObsCommandLog;
use App\Models\ObsStudio;
use App\Models\User;
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

        $this->withToken($studio->agent_token)
            ->postJson('/api/obs-agent/broadcasting/auth', [
                'socket_id' => '12345.67890',
                'channel_name' => "private-obs.studio.{$studio->id}",
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

        $this->withToken($studio->agent_token)
            ->postJson('/api/obs-agent/broadcasting/auth', [
                'socket_id' => '12345.67890',
                'channel_name' => "private-obs.studio.{$otherStudioId}",
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

    public function test_workspace_relay_token_bootstraps_all_active_studios_and_routes_by_studio_id(): void
    {
        Event::fake([ObsStudioStateUpdated::class, ObsCommandAcknowledged::class]);
        config(['obs.relay_token' => 'obs_relay_test_master_key']);

        $activeOne = ObsStudio::factory()->create(['name' => 'Mac OBS', 'is_active' => true]);
        $activeTwo = ObsStudio::factory()->create(['name' => 'Podcast Rig', 'is_active' => true]);
        $inactive = ObsStudio::factory()->create(['name' => 'Archived Rig', 'is_active' => false]);

        $this->withToken('obs_relay_test_master_key')
            ->getJson('/api/obs-agent/bootstrap')
            ->assertOk()
            ->assertJsonPath('mode', 'relay')
            ->assertJsonCount(2, 'studios')
            ->assertJsonPath('reverb.relay_channel', 'private-obs.relay');

        $this->withToken('obs_relay_test_master_key')
            ->postJson('/api/obs-agent/broadcasting/auth', [
                'socket_id' => '12345.67890',
                'channel_name' => 'private-obs.relay',
            ])
            ->assertOk();

        $this->withToken('obs_relay_test_master_key')
            ->postJson('/api/obs-agent/broadcasting/auth', [
                'socket_id' => '12345.67890',
                'channel_name' => "private-obs.studio.{$activeTwo->id}.commands",
            ])
            ->assertOk();

        $this->withToken('obs_relay_test_master_key')
            ->postJson('/api/obs-agent/broadcasting/auth', [
                'socket_id' => '12345.67890',
                'channel_name' => "private-obs.studio.{$inactive->id}.commands",
            ])
            ->assertForbidden();

        $this->withToken('obs_relay_test_master_key')
            ->postJson('/api/obs-agent/state', [
                'studio_id' => $activeOne->id,
                'live_state' => [
                    'currentProgramScene' => 'Keynote Scene',
                ],
            ])
            ->assertOk()
            ->assertJsonPath('status', 'online');

        $activeOne->refresh();
        $this->assertSame('Keynote Scene', $activeOne->getCurrentProgramScene());
    }

    public function test_studio_config_changes_dispatch_catalog_sync_event_while_status_updates_do_not(): void
    {
        Event::fake([ObsRelayCatalogUpdated::class]);

        $studio = ObsStudio::factory()->create(['obs_host' => '127.0.0.1']);
        Event::assertDispatched(ObsRelayCatalogUpdated::class, 1);

        $studio->update(['status' => 'online', 'last_seen_at' => now()]);
        Event::assertDispatched(ObsRelayCatalogUpdated::class, 1);

        $studio->update(['obs_port' => 4456]);
        Event::assertDispatched(ObsRelayCatalogUpdated::class, 2);
    }

    public function test_personal_user_relay_token_isolates_studios_and_channels_per_user(): void
    {
        Event::fake([ObsStudioStateUpdated::class]);

        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $aliceActive = ObsStudio::factory()->for($alice)->create(['name' => 'Alice Studio', 'is_active' => true]);
        ObsStudio::factory()->for($alice)->create(['name' => 'Alice Archived', 'is_active' => false]);
        $bobActive = ObsStudio::factory()->for($bob)->create(['name' => 'Bob Studio', 'is_active' => true]);

        $aliceToken = $alice->getOrCreateRelayToken();
        $this->assertStringStartsWith('usr_', $aliceToken);

        $this->withToken($aliceToken)
            ->getJson('/api/obs-agent/bootstrap')
            ->assertOk()
            ->assertJsonPath('mode', 'relay')
            ->assertJsonPath('scope', 'user')
            ->assertJsonPath('user_id', $alice->id)
            ->assertJsonCount(1, 'studios')
            ->assertJsonPath('studios.0.studio.id', $aliceActive->id)
            ->assertJsonPath('studios.0.studio_channel', "private-obs.studio.{$aliceActive->id}")
            ->assertJsonPath('reverb.relay_channel', "private-obs.user.{$alice->id}.relay");

        $this->withToken($aliceToken)
            ->postJson('/api/obs-agent/broadcasting/auth', [
                'socket_id' => '12345.67890',
                'channel_name' => "private-obs.user.{$alice->id}.relay",
            ])
            ->assertOk();

        $this->withToken($aliceToken)
            ->postJson('/api/obs-agent/broadcasting/auth', [
                'socket_id' => '12345.67890',
                'channel_name' => "private-obs.studio.{$aliceActive->id}",
            ])
            ->assertOk();

        $this->withToken($aliceToken)
            ->postJson('/api/obs-agent/broadcasting/auth', [
                'socket_id' => '12345.67890',
                'channel_name' => "private-obs.studio.{$bobActive->id}",
            ])
            ->assertForbidden();

        $this->withToken($aliceToken)
            ->postJson('/api/obs-agent/broadcasting/auth', [
                'socket_id' => '12345.67890',
                'channel_name' => "private-obs.user.{$bob->id}.relay",
            ])
            ->assertForbidden();

        $this->withToken($aliceToken)
            ->postJson('/api/obs-agent/state', [
                'studio_id' => $bobActive->id,
                'live_state' => ['currentProgramScene' => 'Hijacked'],
            ])
            ->assertNotFound();
    }
}
