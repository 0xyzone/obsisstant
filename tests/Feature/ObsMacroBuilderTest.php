<?php

namespace Tests\Feature;

use App\Filament\Resources\ObsMacros\Pages\CreateObsMacro;
use App\Filament\Resources\ObsMacros\Pages\EditObsMacro;
use App\Models\ObsMacro;
use App\Models\ObsScreen;
use App\Models\ObsStudio;
use App\Models\User;
use App\Services\Obs\ObsRequestCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ObsMacroBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_obs_request_catalog_packs_synced_presets_custom_overrides_and_booleans(): void
    {
        $studio = ObsStudio::factory()->online()->create([
            'live_state' => [
                'scenes' => [
                    ['sceneName' => 'Main Camera'],
                    ['sceneName' => 'Starting Soon'],
                ],
                'audioInputs' => [
                    ['inputName' => 'Mic/Aux'],
                ],
                'sceneItems' => [
                    ['sceneItemId' => 7, 'sourceName' => 'Webcam Frame'],
                ],
            ],
        ]);

        $synced = ObsRequestCatalog::syncedOptions($studio->id);
        $this->assertSame(['Main Camera' => 'Main Camera', 'Starting Soon' => 'Starting Soon'], $synced['scenes']);
        $this->assertSame(['Mic/Aux' => 'Mic/Aux'], $synced['audio_inputs']);
        $this->assertSame(['7' => 'Webcam Frame (#7)'], $synced['scene_items']);

        $packed = ObsRequestCatalog::packSteps([
            [
                'type' => 'obs_command',
                'command_preset' => 'SetCurrentProgramScene',
                'param_scene_choice' => 'Main Camera',
            ],
            [
                'type' => 'obs_command',
                'command_preset' => 'SetInputMute',
                'param_input_choice' => ObsRequestCatalog::CUSTOM_KEY,
                'param_input_custom' => 'Guest Mic',
                'param_inputMuted' => false,
            ],
            [
                'type' => 'obs_command',
                'command_preset' => ObsRequestCatalog::CUSTOM_KEY,
                'custom_request_type' => 'SetSceneTransitionOverride',
                'custom_request_data' => [
                    'transitionName' => 'Stinger',
                    'transitionDuration' => '450',
                ],
            ],
            [
                'type' => 'screen_update',
                'screen_slug' => 'live-lower-thirds',
                'state' => [
                    'show_lower_third' => 'true',
                    'alert_visible' => 'false',
                    'countdown_seconds' => '180',
                ],
            ],
        ]);

        $this->assertSame([
            [
                'type' => 'obs_command',
                'request_type' => 'SetCurrentProgramScene',
                'request_data' => ['sceneName' => 'Main Camera'],
            ],
            [
                'type' => 'obs_command',
                'request_type' => 'SetInputMute',
                'request_data' => ['inputName' => 'Guest Mic', 'inputMuted' => false],
            ],
            [
                'type' => 'obs_command',
                'request_type' => 'SetSceneTransitionOverride',
                'request_data' => [
                    'transitionName' => 'Stinger',
                    'transitionDuration' => 450,
                ],
            ],
            [
                'type' => 'screen_update',
                'screen_slug' => 'live-lower-thirds',
                'state' => [
                    'show_lower_third' => true,
                    'alert_visible' => false,
                    'countdown_seconds' => 180,
                ],
            ],
        ], $packed);

        $unpacked = ObsRequestCatalog::unpackSteps($packed, $studio->id);
        $this->assertSame('SetCurrentProgramScene', $unpacked[0]['command_preset']);
        $this->assertSame('Main Camera', $unpacked[0]['param_scene_choice']);
        $this->assertSame('SetInputMute', $unpacked[1]['command_preset']);
        $this->assertSame(ObsRequestCatalog::CUSTOM_KEY, $unpacked[1]['param_input_choice']);
        $this->assertSame('Guest Mic', $unpacked[1]['param_input_custom']);
        $this->assertSame(ObsRequestCatalog::CUSTOM_KEY, $unpacked[2]['command_preset']);
        $this->assertSame('SetSceneTransitionOverride', $unpacked[2]['custom_request_type']);
        $this->assertSame('true', $unpacked[3]['state']['show_lower_third']);
        $this->assertSame('false', $unpacked[3]['state']['alert_visible']);
    }

    public function test_admin_can_create_and_edit_macro_using_command_picker_and_synced_inputs(): void
    {
        $user = User::factory()->create();
        $studio = ObsStudio::factory()->online()->create([
            'name' => 'Studio Main',
            'live_state' => [
                'scenes' => [
                    ['sceneName' => 'Main Camera'],
                    ['sceneName' => 'BRB Slate'],
                ],
                'audioInputs' => [
                    ['inputName' => 'Host Mic'],
                ],
                'sceneItems' => [],
            ],
        ]);
        ObsScreen::factory()->create([
            'obs_studio_id' => $studio->id,
            'name' => 'Lower Third',
            'slug' => 'lower-third',
        ]);

        $this->actingAs($user);

        $this->get('/admin/obs-macros/create')
            ->assertOk()
            ->assertSee('OBS Action / Request Type')
            ->assertSee('/admin/knowledge-base');

        Livewire::test(CreateObsMacro::class)
            ->fillForm([
                'obs_studio_id' => $studio->id,
                'name' => 'Show Open Sequence',
                'slug' => 'show-open-sequence',
                'sort_order' => 1,
                'steps' => [
                    [
                        'type' => 'obs_command',
                        'command_preset' => 'SetCurrentProgramScene',
                        'param_scene_choice' => 'Main Camera',
                    ],
                    [
                        'type' => 'delay_ms',
                        'delay_ms' => 250,
                    ],
                    [
                        'type' => 'obs_command',
                        'command_preset' => 'SetInputMute',
                        'param_input_choice' => 'Host Mic',
                        'param_inputMuted' => false,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $macro = ObsMacro::query()->where('slug', 'show-open-sequence')->firstOrFail();

        $this->assertSame([
            [
                'type' => 'obs_command',
                'request_type' => 'SetCurrentProgramScene',
                'request_data' => ['sceneName' => 'Main Camera'],
            ],
            [
                'type' => 'delay_ms',
                'delay_ms' => 250,
            ],
            [
                'type' => 'obs_command',
                'request_type' => 'SetInputMute',
                'request_data' => ['inputName' => 'Host Mic', 'inputMuted' => false],
            ],
        ], $macro->steps);

        Livewire::test(EditObsMacro::class, ['record' => $macro->id])
            ->assertOk()
            ->call('save')
            ->assertHasNoFormErrors();

        $macro->refresh();
        $this->assertSame('SetCurrentProgramScene', $macro->steps[0]['request_type']);
        $this->assertSame('Main Camera', $macro->steps[0]['request_data']['sceneName']);
        $this->assertSame('SetInputMute', $macro->steps[2]['request_type']);
        $this->assertFalse($macro->steps[2]['request_data']['inputMuted']);
    }
}
