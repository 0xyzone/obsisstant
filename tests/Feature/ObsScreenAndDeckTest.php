<?php

namespace Tests\Feature;

use App\Events\ObsCommandDispatched;
use App\Events\ObsScreenUpdated;
use App\Models\ObsCommandLog;
use App\Models\ObsMacro;
use App\Models\ObsScreen;
use App\Models\ObsStudio;
use App\Models\User;
use App\Services\Obs\ObsCommandService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Tests\TestCase;

class ObsScreenAndDeckTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_overlay_renders_screen_by_token(): void
    {
        $screen = ObsScreen::factory()->create([
            'name' => 'Keynote Lower Third',
            'template_type' => 'lower_thirds',
            'state' => [
                'guest_name' => 'Aarav Sharma',
                'guest_role' => 'Lead Broadcast Engineer',
                'show_lower_third' => true,
            ],
        ]);

        $response = $this->get(route('obs.screen.show', ['token' => $screen->public_token]));

        $response->assertOk()
            ->assertSee('Aarav Sharma')
            ->assertSee('Lead Broadcast Engineer')
            ->assertSee("obs.screen.{$screen->public_token}");
    }

    public function test_mobile_deck_requires_authentication_and_renders_for_signed_in_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $studio = ObsStudio::factory()->online()->for($user)->create(['name' => 'Main Studio']);

        $this->get(route('obs.deck.show', ['studio' => $studio->slug]))
            ->assertRedirect();

        $this->actingAs($otherUser)
            ->get(route('obs.deck.show', ['studio' => $studio->slug]))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('obs.deck.show', ['studio' => $studio->slug]))
            ->assertOk()
            ->assertSee('Main Studio')
            ->assertSee('Mobile Wireless Stream Deck');
    }

    public function test_mobile_deck_can_dispatch_command_run_macro_and_update_live_screen(): void
    {
        Event::fake([ObsCommandDispatched::class, ObsScreenUpdated::class]);

        $user = User::factory()->create();
        $studio = ObsStudio::factory()->online()->for($user)->create();
        $screen = ObsScreen::factory()->create([
            'obs_studio_id' => $studio->id,
            'slug' => 'hero-overlay',
        ]);
        $macro = ObsMacro::factory()->create([
            'obs_studio_id' => $studio->id,
            'steps' => [
                [
                    'type' => 'screen_update',
                    'screen_slug' => 'hero-overlay',
                    'state' => ['guest_name' => 'Macro Guest'],
                ],
                [
                    'type' => 'obs_command',
                    'request_type' => 'SetCurrentProgramScene',
                    'request_data' => ['sceneName' => 'Main Scene'],
                ],
                [
                    'type' => 'delay_ms',
                    'delay_ms' => 300,
                ],
            ],
        ]);

        $this->actingAs($user)
            ->postJson(route('obs.deck.command', ['studio' => $studio->slug]), [
                'request_type' => 'ToggleStream',
            ])
            ->assertOk()
            ->assertJsonPath('request_type', 'ToggleStream');

        $this->actingAs($user)
            ->postJson(route('obs.deck.macro', ['studio' => $studio->slug, 'macro' => $macro->id]))
            ->assertOk();

        $screen->refresh();
        $this->assertSame('Macro Guest', $screen->state['guest_name']);

        $this->actingAs($user)
            ->postJson(route('obs.deck.screen-live', ['screen' => $screen->id]), [
                'state' => ['alert_visible' => true, 'alert_message' => 'Going Live!'],
            ])
            ->assertOk();

        Event::assertDispatched(ObsCommandDispatched::class, 2);
        Event::assertDispatched(ObsScreenUpdated::class, 2);
    }

    public function test_command_service_dispatches_push_browser_screen_command(): void
    {
        Event::fake([ObsCommandDispatched::class]);

        $studio = ObsStudio::factory()->create();
        $screen = ObsScreen::factory()->create([
            'obs_studio_id' => $studio->id,
            'width' => 1920,
            'height' => 1080,
            'obs_scene_name' => 'Showcase Scene',
        ]);

        $log = app(ObsCommandService::class)->pushScreenToObs($studio, $screen);

        $this->assertSame('obsisstant.PushBrowserScreen', $log->request_type);
        $this->assertSame('Showcase Scene', $log->request_data['sceneName']);
        $this->assertSame($screen->getOverlayUrl(), $log->request_data['url']);
        Event::assertDispatched(ObsCommandDispatched::class);
    }

    public function test_mobile_deck_switches_scenes_handles_offline_state_and_renders_studio_switcher(): void
    {
        Event::fake([ObsCommandDispatched::class]);

        $user = User::factory()->create();
        $primary = ObsStudio::factory()->for($user)->create([
            'name' => 'Studio One',
            'status' => 'offline',
            'last_seen_at' => null,
            'live_state' => [
                'currentProgramScene' => 'Intro',
                'scenes' => [
                    ['sceneName' => 'Intro', 'sceneIndex' => 0],
                    ['sceneName' => 'BRB', 'sceneIndex' => 1],
                ],
            ],
        ]);
        $secondary = ObsStudio::factory()->online()->for($user)->create(['name' => 'Studio Two']);

        $this->actingAs($user)
            ->get(route('obs.deck.show', ['studio' => $primary->slug]))
            ->assertOk()
            ->assertSee('deck-studio-switcher', false)
            ->assertSee('Studio Two')
            ->assertSee('deck-offline-banner', false)
            ->assertSee('deck-action-feedback', false)
            ->assertSee('Connect / Install Agent')
            ->assertSee('Intro')
            ->assertSee('BRB')
            ->assertSee('sendDeckCommand', false)
            ->assertSee('rollbackSceneSelection', false)
            ->assertSee('OBS Agent did not respond', false)
            ->assertSee(json_encode('/deck/'.$primary->slug.'/command'), false)
            ->assertSee(json_encode('/studio/'.$primary->slug.'/snapshot'), false);

        $this->actingAs($user)
            ->get(route('obs.deck.show', ['studio' => $secondary->slug]))
            ->assertOk()
            ->assertSee('deck-offline-banner" class="deck-offline-banner"  hidden', false);

        $this->actingAs($user)
            ->postJson(route('obs.deck.command', ['studio' => $secondary->slug]), [
                'request_type' => 'SetCurrentProgramScene',
                'request_data' => ['sceneName' => 'BRB'],
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('request_type', 'SetCurrentProgramScene');

        Event::assertDispatched(ObsCommandDispatched::class, static function (ObsCommandDispatched $event): bool {
            return $event->commandLog->request_type === 'SetCurrentProgramScene'
                && ($event->commandLog->request_data['sceneName'] ?? null) === 'BRB';
        });
    }

    public function test_pending_commands_endpoint_and_safe_broadcast_fallback_for_scene_commands(): void
    {
        $user = User::factory()->create(['relay_token' => 'usr_test_relay_pending_token']);
        $otherUser = User::factory()->create();

        $studio = ObsStudio::factory()->online()->for($user)->create([
            'agent_token' => 'obs_test_studio_pending_token',
        ]);
        $otherStudio = ObsStudio::factory()->online()->for($otherUser)->create();

        $activeLog = ObsCommandLog::query()->create([
            'obs_studio_id' => $studio->id,
            'user_id' => $user->id,
            'command_uuid' => 'uuid-active-scene-cmd',
            'request_type' => 'SetCurrentProgramScene',
            'request_data' => ['sceneName' => 'Starting Soon'],
            'status' => 'dispatched',
        ]);

        ObsCommandLog::query()->create([
            'obs_studio_id' => $studio->id,
            'user_id' => $user->id,
            'command_uuid' => 'uuid-done-scene-cmd',
            'request_type' => 'SetCurrentProgramScene',
            'request_data' => ['sceneName' => 'BRB'],
            'status' => 'succeeded',
        ]);

        ObsCommandLog::query()->create([
            'obs_studio_id' => $otherStudio->id,
            'user_id' => $otherUser->id,
            'command_uuid' => 'uuid-other-user-cmd',
            'request_type' => 'SetCurrentProgramScene',
            'request_data' => ['sceneName' => 'Secret'],
            'status' => 'dispatched',
        ]);

        // User relay mode fetches only active dispatched commands on owned studios
        $this->withHeader('Authorization', 'Bearer usr_test_relay_pending_token')
            ->getJson(route('api.obs-agent.commands.pending'))
            ->assertOk()
            ->assertJsonCount(1, 'studios')
            ->assertJsonPath('studios.0.studio_id', $studio->id)
            ->assertJsonCount(1, 'studios.0.pending_commands')
            ->assertJsonPath('studios.0.pending_commands.0.command_uuid', $activeLog->command_uuid)
            ->assertJsonPath('studios.0.pending_commands.0.request_data.sceneName', 'Starting Soon');

        // Studio token mode also returns pending_commands cleanly
        $this->withHeader('Authorization', 'Bearer obs_test_studio_pending_token')
            ->getJson(route('api.obs-agent.commands.pending'))
            ->assertOk()
            ->assertJsonCount(1, 'pending_commands')
            ->assertJsonPath('pending_commands.0.command_uuid', $activeLog->command_uuid);

        // Even if Reverb broadcaster throws, dispatching a scene command persists safely
        Event::listen(ObsCommandDispatched::class, static function (): void {
            throw new RuntimeException('Simulated Reverb offline');
        });

        $response = $this->actingAs($user)
            ->postJson(route('obs.deck.command', ['studio' => $studio->slug]), [
                'request_type' => 'SetCurrentProgramScene',
                'request_data' => ['sceneName' => 'Ending'],
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('request_type', 'SetCurrentProgramScene');

        $newUuid = (string) $response->json('command_uuid');
        $this->assertDatabaseHas('obs_command_logs', [
            'command_uuid' => $newUuid,
            'status' => 'dispatched',
        ]);

        $agentJs = file_get_contents(base_path('obs-agent/src/index.js')) ?: '';
        $this->assertStringContainsString('Pusher.Runtime.createWebSocket', $agentJs);
        $this->assertStringContainsString('pollPendingCommands', $agentJs);
        $this->assertStringContainsString('client-obs.command', $agentJs);
        $this->assertStringContainsString('ackCommandWithRetry', $agentJs);

        // Client-generated UUIDs are preserved across Mobile Deck & Control Center dispatches
        $clientDeckUuid = '11111111-2222-4333-8444-555555555555';
        $this->actingAs($user)
            ->postJson(route('obs.deck.command', ['studio' => $studio->slug]), [
                'command_uuid' => $clientDeckUuid,
                'request_type' => 'SetCurrentProgramScene',
                'request_data' => ['sceneName' => 'Intro'],
            ])
            ->assertOk()
            ->assertJsonPath('command_uuid', $clientDeckUuid);

        $clientStudioUuid = 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee';
        $this->actingAs($user)
            ->postJson(route('studio.command', ['studio' => $studio->slug]), [
                'command_uuid' => $clientStudioUuid,
                'request_type' => 'SetCurrentProgramScene',
                'request_data' => ['sceneName' => 'Main'],
            ])
            ->assertOk()
            ->assertJsonPath('command_uuid', $clientStudioUuid);
    }
}
