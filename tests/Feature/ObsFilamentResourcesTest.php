<?php

namespace Tests\Feature;

use App\Events\ObsCommandDispatched;
use App\Events\ObsScreenUpdated;
use App\Filament\Pages\ObsControlDeck;
use App\Filament\Resources\ObsMacros\Pages\ListObsMacros;
use App\Filament\Resources\ObsScreens\Pages\EditObsScreen;
use App\Filament\Resources\ObsScreens\Pages\ListObsScreens;
use App\Filament\Resources\ObsStudios\Pages\CreateObsStudio;
use App\Filament\Resources\ObsStudios\Pages\EditObsStudio;
use App\Filament\Resources\ObsStudios\Pages\ListObsStudios;
use App\Models\ObsMacro;
use App\Models\ObsScreen;
use App\Models\ObsStudio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;

class ObsFilamentResourcesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_render_and_use_obs_control_deck(): void
    {
        Event::fake([ObsCommandDispatched::class, ObsScreenUpdated::class]);

        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $studio = ObsStudio::factory()->online()->for($user)->create(['name' => 'Studio Alpha']);
        $foreignStudio = ObsStudio::factory()->online()->for($otherUser)->create(['name' => 'Studio Foreign']);
        $screen = ObsScreen::factory()->create(['obs_studio_id' => $studio->id]);
        $macro = ObsMacro::factory()->create(['obs_studio_id' => $studio->id]);

        $this->actingAs($user);

        Livewire::test(ObsControlDeck::class)
            ->assertOk()
            ->assertSee('Studio Alpha')
            ->assertDontSee('Studio Foreign')
            ->set('newSceneName', 'Interview Cam')
            ->call('createNewScene')
            ->call('switchScene', 'Main Scene')
            ->call('toggleStudioMode')
            ->call('runMacro', $macro->id)
            ->set('liveGuestName', 'Live Speaker')
            ->call('updateLiveOverlay', true)
            ->set('liveAlertMessage', 'Welcome!')
            ->call('triggerLiveAlert', true)
            ->assertHasNoErrors();

        $screen->refresh();
        $this->assertSame('Live Speaker', $screen->state['guest_name']);
        $this->assertTrue($screen->state['alert_visible']);
        Event::assertDispatched(ObsCommandDispatched::class);
        Event::assertDispatched(ObsScreenUpdated::class);
    }

    public function test_admin_can_list_and_create_obs_studios_screens_and_macros(): void
    {
        Event::fake([ObsScreenUpdated::class]);

        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $studio = ObsStudio::factory()->for($user)->create();
        $foreignStudio = ObsStudio::factory()->for($otherUser)->create();
        $screen = ObsScreen::factory()->create(['obs_studio_id' => $studio->id]);
        $foreignScreen = ObsScreen::factory()->create(['obs_studio_id' => $foreignStudio->id]);
        $macro = ObsMacro::factory()->create(['obs_studio_id' => $studio->id]);
        $foreignMacro = ObsMacro::factory()->create(['obs_studio_id' => $foreignStudio->id]);

        $this->actingAs($user);

        Livewire::test(ListObsStudios::class)
            ->assertCanSeeTableRecords([$studio])
            ->assertCanNotSeeTableRecords([$foreignStudio]);
        Livewire::test(ListObsScreens::class)
            ->assertCanSeeTableRecords([$screen])
            ->assertCanNotSeeTableRecords([$foreignScreen]);
        Livewire::test(ListObsMacros::class)
            ->assertCanSeeTableRecords([$macro])
            ->assertCanNotSeeTableRecords([$foreignMacro]);

        Livewire::test(CreateObsStudio::class)
            ->fillForm([
                'name' => 'Podcast Room',
                'slug' => 'podcast-room',
                'obs_host' => '192.168.1.50',
                'obs_port' => 4455,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('obs_studios', [
            'name' => 'Podcast Room',
            'slug' => 'podcast-room',
        ]);

        Livewire::test(EditObsScreen::class, ['record' => $screen->id])
            ->fillForm([
                'name' => 'Updated Overlay',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $createdStudio = ObsStudio::query()->where('slug', 'podcast-room')->firstOrFail();

        Livewire::test(EditObsStudio::class, ['record' => $createdStudio->id])
            ->assertFormSet([
                'agent_token' => $createdStudio->agent_token,
            ]);

        Event::assertDispatched(ObsScreenUpdated::class);
    }
}
