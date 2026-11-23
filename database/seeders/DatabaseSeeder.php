<?php

namespace Database\Seeders;

use App\Models\ObsMacro;
use App\Models\ObsScreen;
use App\Models\ObsStudio;
use App\Models\User;
use App\Services\Obs\ObsScreenPresetService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Super Admin',
                'password' => 'password',
                'contact_number' => '9817208300',
                'email' => null,
                'current_address' => null,
            ],
        );

        $studio = ObsStudio::query()->firstOrCreate(
            ['slug' => 'main-studio'],
            [
                'name' => 'Main Studio',
                'agent_token' => 'obs_'.Str::random(48),
                'obs_host' => '127.0.0.1',
                'obs_port' => 4455,
                'status' => 'offline',
                'is_active' => true,
            ],
        );

        $presets = [
            [
                'slug' => 'starting-soon',
                'name' => 'Starting Soon',
                'template_type' => 'starting_soon',
                'obs_scene_name' => 'Starting Soon',
                'obs_source_name' => 'Obsisstant - Starting Soon',
            ],
            [
                'slug' => 'live-lower-thirds',
                'name' => 'Live Lower Thirds',
                'template_type' => 'lower_thirds',
                'obs_scene_name' => 'Main Scene',
                'obs_source_name' => 'Obsisstant - Live Lower Thirds',
            ],
            [
                'slug' => 'be-right-back',
                'name' => 'Be Right Back',
                'template_type' => 'brb',
                'obs_scene_name' => 'BRB',
                'obs_source_name' => 'Obsisstant - BRB',
            ],
        ];

        foreach ($presets as $preset) {
            ObsScreen::query()->firstOrCreate(
                ['slug' => $preset['slug']],
                [
                    'obs_studio_id' => $studio->id,
                    'name' => $preset['name'],
                    'public_token' => Str::random(32),
                    'template_type' => $preset['template_type'],
                    'width' => 1920,
                    'height' => 1080,
                    'theme' => ObsScreenPresetService::defaultTheme($preset['template_type']),
                    'state' => ObsScreenPresetService::defaultState($preset['template_type']),
                    'obs_scene_name' => $preset['obs_scene_name'],
                    'obs_source_name' => $preset['obs_source_name'],
                    'is_live' => true,
                ],
            );
        }

        ObsMacro::query()->firstOrCreate(
            ['slug' => 'go-live-sequence'],
            [
                'obs_studio_id' => $studio->id,
                'name' => 'Go Live Sequence',
                'icon' => 'heroicon-o-play-circle',
                'color' => 'gold',
                'description' => 'Switch to Main Scene, show Lower Thirds overlay, and start recording.',
                'sort_order' => 1,
                'steps' => [
                    [
                        'type' => 'obs_command',
                        'request_type' => 'SetCurrentProgramScene',
                        'request_data' => ['sceneName' => 'Main Scene'],
                    ],
                    [
                        'type' => 'screen_update',
                        'screen_slug' => 'live-lower-thirds',
                        'state' => ['show_lower_third' => true, 'alert_visible' => false],
                    ],
                    [
                        'type' => 'obs_command',
                        'request_type' => 'StartRecord',
                        'request_data' => [],
                    ],
                ],
            ],
        );

        ObsMacro::query()->firstOrCreate(
            ['slug' => 'intermission-brb'],
            [
                'obs_studio_id' => $studio->id,
                'name' => 'Intermission (BRB)',
                'icon' => 'heroicon-o-pause-circle',
                'color' => 'amber',
                'description' => 'Switch to BRB scene and start a 3-minute return countdown.',
                'sort_order' => 2,
                'steps' => [
                    [
                        'type' => 'screen_update',
                        'screen_slug' => 'be-right-back',
                        'state' => [
                            'show_countdown' => true,
                            'countdown_seconds' => 180,
                        ],
                    ],
                    [
                        'type' => 'obs_command',
                        'request_type' => 'SetCurrentProgramScene',
                        'request_data' => ['sceneName' => 'BRB'],
                    ],
                ],
            ],
        );
    }
}
