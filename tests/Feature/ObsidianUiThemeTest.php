<?php

namespace Tests\Feature;

use App\Models\ObsMacro;
use App\Models\ObsScreen;
use App\Models\ObsStudio;
use App\Models\User;
use App\Services\ChangelogService;
use App\Services\ReverbStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSee('Reverb active')
            ->assertSee('Control every OBS frame')
            ->assertSee('Starting Soon')
            ->assertSee('Lower Thirds')
            ->assertSee('poppins:400,500,600')
            ->assertSee('#CDAD69')
            ->assertSee('#F4E185')
            ->assertSee('animation-timeline: view()', false)
            ->assertSee('@starting-style', false)
            ->assertSee('transition-behavior: allow-discrete', false)
            ->assertSee('/admin/obs-control-deck')
            ->assertSee(route('obs.deck.index'))
            ->assertSee(route('changelog'))
            ->assertDontSee('poppins:300,400,500,600,700,800', false)
            ->assertDontSee('--gold-foil', false)
            ->assertDontSee('ambient-canvas', false)
            ->assertDontSee('ambient-grid', false);
    }

    public function test_reverb_status_pill_and_version_pill_appear_across_app_and_filament_panels(): void
    {
        $user = User::factory()->create();
        $studio = ObsStudio::factory()->online()->create(['name' => 'Studio Alpha']);
        $versionTag = 'v'.ChangelogService::getCurrentVersion();

        ReverbStatusService::fakeUsing(static fn (): bool => false);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('obs-reverb-pill is-offline', false)
            ->assertSee('Reverb offline');

        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('obs-brand-version-pill', false)
            ->assertSee($versionTag)
            ->assertSee('obs-reverb-pill is-offline', false)
            ->assertSee('Reverb offline');

        ReverbStatusService::fakeUsing(static fn (): bool => true);

        $this->get('/changelog')
            ->assertOk()
            ->assertSee('obs-reverb-pill', false)
            ->assertSee('Reverb active')
            ->assertSee($versionTag);

        $this->actingAs($user)
            ->get(route('obs.deck.show', ['studio' => $studio->slug]))
            ->assertOk()
            ->assertSee('obs-brand-version-pill', false)
            ->assertSee($versionTag)
            ->assertSee('obs-reverb-pill', false)
            ->assertSee('Reverb active');

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
        $studio = ObsStudio::factory()->online()->create(['name' => 'Studio Bravo']);
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
}
