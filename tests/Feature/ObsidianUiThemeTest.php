<?php

namespace Tests\Feature;

use App\Models\ObsMacro;
use App\Models\ObsScreen;
use App\Models\ObsStudio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ObsidianUiThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_renders_product_showcase_with_live_counts_and_presets(): void
    {
        $studio = ObsStudio::factory()->online()->create(['name' => 'Alpha Rig']);
        ObsScreen::factory()->count(2)->create(['obs_studio_id' => $studio->id]);
        ObsMacro::factory()->create(['obs_studio_id' => $studio->id]);

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('OBSISSTANT')
            ->assertSee('COMMAND')
            ->assertSee('EVERY OBS')
            ->assertSee('Starting Soon')
            ->assertSee('Lower Thirds')
            ->assertSee('poppins:300,400,500,600,700,800')
            ->assertSee('#CDAD69')
            ->assertSee('#F4E185')
            ->assertSee('/admin/obs-control-deck')
            ->assertSee(route('obs.deck.index'))
            ->assertSee(route('changelog'));
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
