<?php

namespace Tests\Feature;

use App\Events\ObsCommandDispatched;
use App\Events\ObsScreenUpdated;
use App\Models\ObsMacro;
use App\Models\ObsScreen;
use App\Models\ObsStudio;
use App\Models\User;
use App\Services\Obs\ObsCommandService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
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
        $studio = ObsStudio::factory()->online()->create(['name' => 'Main Studio']);

        $this->get(route('obs.deck.show', ['studio' => $studio->slug]))
            ->assertRedirect();

        $user = User::factory()->create();

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
        $studio = ObsStudio::factory()->online()->create();
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
}
