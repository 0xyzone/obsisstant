<?php

namespace Tests\Feature;

use App\Models\ObsMacro;
use App\Models\ObsScreen;
use App\Models\ObsStudio;
use App\Models\ScreenCollection;
use App\Models\User;
use App\Services\ChangelogService;
use App\Services\ReverbStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ObsidianUiThemeTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        ReverbStatusService::resetFake();

        parent::tearDown();
    }

    public function test_landing_page_renders_unslopped_showcase_with_live_counts_presets_and_status_badges(): void
    {
        ReverbStatusService::fakeUsing(static fn (): bool => true);

        $studio = ObsStudio::factory()->online()->create(['name' => 'Alpha Rig']);
        ObsScreen::factory()->count(2)->create(['obs_studio_id' => $studio->id]);
        ObsMacro::factory()->create(['obs_studio_id' => $studio->id]);

        $versionTag = 'v'.ChangelogService::getCurrentVersion();
        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('Obsisstant')
            ->assertSee($versionTag)
            ->assertDontSee('obs-reverb-pill', false)
            ->assertSee('Run OBS Studio')
            ->assertSee('Starting Soon')
            ->assertSee('Lower Thirds')
            ->assertSee('poppins:200,300,400,500,600')
            ->assertSee('#CDAD69')
            ->assertSee('#F4E185')
            ->assertSee('animation-timeline: view()', false)
            ->assertSee('@starting-style', false)
            ->assertSee('transition-behavior: allow-discrete', false)
            ->assertSee('data-obs-mascot', false)
            ->assertSee('obs-mascot.js', false)
            ->assertSee('Everything you use live.')
            ->assertSee('Made for real broadcasts.')
            ->assertSee('Common questions.')
            ->assertSee('/admin/obs-control-deck')
            ->assertSee(route('obs.deck.index'))
            ->assertSee(route('changelog'))
            ->assertDontSee('--gold-foil', false)
            ->assertDontSee('ambient-canvas', false)
            ->assertDontSee('ambient-grid', false);
    }

    public function test_reverb_status_pill_appears_only_in_filament_panel_while_version_pill_stays_app_wide(): void
    {
        $user = User::factory()->create();
        $studio = ObsStudio::factory()->online()->for($user)->create(['name' => 'Studio Alpha']);
        $versionTag = 'v'.ChangelogService::getCurrentVersion();

        ReverbStatusService::fakeUsing(static fn (): bool => false);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('obs-reverb-pill', false)
            ->assertDontSee('Reverb offline');

        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('obs-brand-version-pill', false)
            ->assertSee($versionTag)
            ->assertSee('obs-reverb-pill is-offline', false)
            ->assertSee('Reverb offline');

        ReverbStatusService::fakeUsing(static fn (): bool => true);

        $this->get('/changelog')
            ->assertOk()
            ->assertDontSee('obs-reverb-pill', false)
            ->assertDontSee('data-reverb-status=', false)
            ->assertSee($versionTag);

        $this->actingAs($user)
            ->get(route('obs.deck.show', ['studio' => $studio->slug]))
            ->assertOk()
            ->assertSee('obs-brand-version-pill', false)
            ->assertSee($versionTag)
            ->assertDontSee('obs-reverb-pill', false)
            ->assertDontSee('Reverb active');

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk()
            ->assertSee('obs-brand-version-pill', false)
            ->assertSee($versionTag)
            ->assertSee('obs-reverb-pill', false)
            ->assertSee('Reverb active');

        ReverbStatusService::fakeUsing(static fn (): bool => false);

        $this->actingAs($user)
            ->get('/admin/obs-control-deck')
            ->assertOk()
            ->assertSee('obs-reverb-pill is-offline', false)
            ->assertSee('Reverb offline');
    }

    public function test_reverb_status_service_probes_tcp_socket_accurately(): void
    {
        $service = new ReverbStatusService(0.15);
        $server = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        $this->assertNotFalse($server, "Failed to bind ephemeral socket: {$errstr}");

        $address = stream_socket_get_name($server, false);
        $this->assertIsString($address);
        [, $port] = explode(':', $address);

        $this->assertTrue($service->probeTcpSocket('127.0.0.1', (int) $port));

        fclose($server);

        $this->assertFalse($service->probeTcpSocket('127.0.0.1', (int) $port));
    }

    public function test_filament_login_renders_forced_obsidian_theme_and_clean_sign_in_heading(): void
    {
        $response = $this->get('/admin/login');

        $response->assertOk()
            ->assertSee('Sign in to Obsisstant')
            ->assertSee('filament-obs-theme.css')
            ->assertSee('poppins:400,500,600')
            ->assertSee('dark', false);
    }

    public function test_authenticated_filament_dashboard_renders_studio_workspace_and_mobile_dock(): void
    {
        $user = User::factory()->create();
        $studio = ObsStudio::factory()->online()->for($user)->create(['name' => 'Studio Bravo']);
        ObsScreen::factory()->create(['obs_studio_id' => $studio->id]);
        ObsMacro::factory()->create(['obs_studio_id' => $studio->id]);

        $this->assertFileExists(public_path('css/filament-obs-theme.css'));
        $themeCss = (string) file_get_contents(public_path('css/filament-obs-theme.css'));
        $this->assertStringContainsString('.fi-section:not(.fi-section-not-contained)', $themeCss);
        $this->assertStringContainsString('.fi-section.fi-section-not-contained', $themeCss);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertOk()
            ->assertSee('Obsisstant')
            ->assertSee('Studios online')
            ->assertSee('Live screens')
            ->assertSee('Studio roster')
            ->assertSee('Studio Bravo')
            ->assertSee('Recent commands')
            ->assertSee('obs-mobile-dock')
            ->assertSee('filament-obs-theme.css');

        $deckResponse = $this->actingAs($user)->get('/admin/obs-control-deck');

        $deckResponse->assertOk()
            ->assertSee('obs-deck-stack')
            ->assertSee('obs-telemetry-grid')
            ->assertSee('obs-deck-two-col')
            ->assertSee('obs-sidebar-footer')
            ->assertDontSee('sm:grid-cols-5')
            ->assertDontSee('lg:grid-cols-2');
    }

    public function test_reverb_status_requires_reverb_driver_credentials_and_authentic_reverb_server(): void
    {
        $server = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        $this->assertNotFalse($server, "Failed to bind ephemeral socket: {$errstr}");

        $address = stream_socket_get_name($server, false);
        $this->assertIsString($address);
        [, $port] = explode(':', $address);
        $portInt = (int) $port;

        try {
            // 1. Even with an open TCP port, BROADCAST_CONNECTION=log must report offline and disable client Echo
            config([
                'broadcasting.default' => 'log',
                'reverb.servers.reverb.host' => '127.0.0.1',
                'reverb.servers.reverb.port' => $portInt,
                'reverb.apps.apps.0.app_id' => '',
                'reverb.apps.apps.0.key' => '',
                'reverb.apps.apps.0.secret' => '',
            ]);

            $service = new ReverbStatusService(0.15);
            $status = $service->status();

            $this->assertFalse($status['active']);
            $this->assertSame('Reverb offline', $status['label']);
            $this->assertStringContainsString('log', $status['reason']);
            $this->assertNull(ReverbStatusService::clientEchoConfig());

            // 2. Empty Reverb credentials with BROADCAST_CONNECTION=reverb must still report offline
            config([
                'broadcasting.default' => 'reverb',
                'reverb.apps.apps.0.app_id' => '',
                'reverb.apps.apps.0.key' => '',
                'reverb.apps.apps.0.secret' => '',
            ]);

            $statusMissingCreds = $service->status();
            $this->assertFalse($statusMissingCreds['active']);
            $this->assertSame('Reverb offline', $statusMissingCreds['label']);
            $this->assertNull(ReverbStatusService::clientEchoConfig());

            // 3. Valid credentials pointing at a non-Reverb TCP port must not produce a false-positive "Reverb active"
            config([
                'broadcasting.default' => 'reverb',
                'reverb.apps.apps.0.app_id' => '950876',
                'reverb.apps.apps.0.key' => 'obsktest1234',
                'reverb.apps.apps.0.secret' => 'obsstestsecret',
                'broadcasting.connections.reverb.options.host' => '127.0.0.1',
                'broadcasting.connections.reverb.options.port' => $portInt,
                'broadcasting.connections.reverb.options.scheme' => 'http',
            ]);

            $statusNonReverb = $service->status();
            $this->assertFalse($statusNonReverb['active']);
            $this->assertSame('Reverb offline', $statusNonReverb['label']);
            $this->assertIsArray(ReverbStatusService::clientEchoConfig());

            // Filament panel must not initialize window.Echo when Reverb key/driver is inactive
            config(['broadcasting.default' => 'log', 'reverb.apps.apps.0.key' => '']);
            $user = User::factory()->create();
            $this->actingAs($user)
                ->get('/admin/obs-control-deck')
                ->assertOk()
                ->assertSee('Reverb offline')
                ->assertDontSee('broadcaster: \'reverb\'', false);
        } finally {
            fclose($server);
        }
    }

    public function test_lumi_mascot_appears_across_app_views_excluding_builder_and_stage(): void
    {
        $user = User::factory()->create();
        $studio = ObsStudio::factory()->online()->for($user)->create(['name' => 'Broadcast Main']);
        $this->actingAs($user)
            ->postJson(route('studio.control-mode', ['studio' => $studio->slug]), ['control_mode' => 'screens'])
            ->assertOk();
        $collection = ScreenCollection::findOrFail($studio->fresh()->active_screen_collection_id);
        auth()->logout();

        // Public pages
        $this->get(route('home'))->assertOk()->assertSee('data-obs-mascot', false)->assertSee('data-mascot-mode="hero-guide"', false);
        $this->get(route('changelog'))->assertOk()->assertSee('data-obs-mascot', false)->assertSee('data-mascot-mode="floating-assistant"', false);
        $this->get(route('knowledge-base'))->assertOk()->assertSee('data-obs-mascot', false)->assertSee('data-mascot-mode="floating-assistant"', false);
        $this->get('/admin/login')->assertOk()->assertSee('data-obs-mascot', false)->assertSee('data-mascot-mode="floating-assistant"', false);

        // Authenticated Filament & Studio pages
        $this->actingAs($user)->get('/admin')->assertOk()->assertSee('data-obs-mascot', false);
        $this->actingAs($user)->get(route('studio.show', ['studio' => $studio->slug]))->assertOk()->assertSee('data-obs-mascot', false);
        $this->actingAs($user)->get(route('obs.deck.show', ['studio' => $studio->slug]))->assertOk()->assertSee('data-obs-mascot', false);

        // Excluded distraction-free surfaces: Canvas Builder & Stage Player
        $this->actingAs($user)
            ->get(route('studio.collections.builder', ['studio' => $studio->slug, 'collection' => $collection->id]))
            ->assertOk()
            ->assertDontSee('data-obs-mascot', false);

        $this->get(route('obs.collection.show', ['token' => $collection->public_token]))
            ->assertOk()
            ->assertDontSee('data-obs-mascot', false);
    }

    public function test_oxy_assistant_chats_naturally_generates_copy_and_executes_live_studio_actions(): void
    {
        // Guest: natural small talk & follow-up history work
        $this->postJson(route('oxy.ask'), ['message' => 'Hey Oxy, how are you?'])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('reaction', 'cheer');

        // Guest: copy generation works
        $this->postJson(route('oxy.ask'), ['message' => 'Suggest lower-third copy'])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonStructure(['reply', 'reaction', 'links', 'suggestions']);

        // Guest: KB search + follow-up context work
        $kbResponse = $this->postJson(route('oxy.ask'), ['message' => 'How do I pair OBS?'])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->postJson(route('oxy.ask'), [
            'message' => 'Tell me more',
            'history' => [
                ['role' => 'user', 'text' => 'How do I pair OBS?'],
                ['role' => 'assistant', 'text' => (string) $kbResponse->json('reply')],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        // Guest: live studio actions require authentication
        $this->postJson(route('oxy.ask'), ['message' => 'Start 5m timer'])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('reaction', 'peek');

        $user = User::factory()->create();
        $studio = ObsStudio::factory()->online()->for($user)->create(['name' => 'Live Desk']);
        $this->actingAs($user)
            ->postJson(route('studio.control-mode', ['studio' => $studio->slug]), ['control_mode' => 'screens'])
            ->assertOk();
        $collection = ScreenCollection::findOrFail($studio->fresh()->active_screen_collection_id);
        $collection->update([
            'runtime_state' => [
                'timer' => ['duration_seconds' => 300, 'remaining_seconds' => 300, 'running' => false, 'mode' => 'countdown', 'label' => ''],
                'lower_third' => ['visible' => false, 'badge' => 'LIVE', 'title' => 'Guest', 'subtitle' => 'Host'],
            ],
        ]);

        // Signed-in operator: live studio actions mutate runtime_state and check status
        $this->actingAs($user)
            ->postJson(route('oxy.ask'), ['message' => 'Start 10m timer', 'studio_slug' => $studio->slug])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('reaction', 'cheer');

        $collection->refresh();
        $this->assertSame('running', data_get($collection->runtime_state, 'timer.status'));
        $this->assertSame(600, data_get($collection->runtime_state, 'timer.duration_seconds'));

        $this->actingAs($user)
            ->postJson(route('oxy.ask'), ['message' => 'Show lower third', 'studio_slug' => $studio->slug])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $collection->refresh();
        $this->assertTrue((bool) data_get($collection->runtime_state, 'lower_third.visible'));

        $this->actingAs($user)
            ->postJson(route('oxy.ask'), ['message' => 'Check studio status', 'studio_slug' => $studio->slug])
            ->assertOk()
            ->assertJsonPath('ok', true);

        // When GEMINI_API_KEY is configured, signed-in operators get Gemini Interactions API responses (while guests stay on local engine)
        config(['services.gemini.key' => 'test-gemini-key', 'services.gemini.model' => 'gemini-3.5-flash-lite']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'id' => 'int_oxy_test',
                'steps' => [
                    [
                        'type' => 'model_output',
                        'content' => [
                            ['type' => 'text', 'text' => 'Hey! Here is a custom Gemini tip for your stream setup.'],
                        ],
                    ],
                ],
            ], 200),
        ]);

        auth()->logout();
        $this->app['auth']->forgetGuards();

        // Guest still uses local engine (0 HTTP calls to Gemini)
        $this->postJson(route('oxy.ask'), ['message' => 'Can you give me a custom streaming tip?'])
            ->assertOk()
            ->assertDontSee('custom Gemini tip');
        Http::assertNothingSent();

        // Signed-in user uses Gemini Interactions API
        $this->actingAs($user)
            ->postJson(route('oxy.ask'), ['message' => 'Can you give me a custom streaming tip?', 'studio_slug' => $studio->slug])
            ->assertOk()
            ->assertJsonPath('reply', 'Hey! Here is a custom Gemini tip for your stream setup.');
        Http::assertSentCount(1);
    }
}
