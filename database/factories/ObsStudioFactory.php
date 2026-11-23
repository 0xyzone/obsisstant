<?php

namespace Database\Factories;

use App\Models\ObsStudio;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ObsStudio>
 */
class ObsStudioFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true).' Studio';

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'agent_token' => 'obs_'.Str::random(48),
            'obs_host' => '127.0.0.1',
            'obs_port' => 4455,
            'obs_password' => 'secret-obs-pass',
            'status' => 'offline',
            'is_active' => true,
            'last_seen_at' => null,
            'live_state' => [
                'currentProgramScene' => 'Main Scene',
                'currentPreviewScene' => 'Starting Soon',
                'studioModeEnabled' => false,
                'scenes' => [
                    ['sceneName' => 'Starting Soon', 'sceneIndex' => 0],
                    ['sceneName' => 'Main Scene', 'sceneIndex' => 1],
                    ['sceneName' => 'BRB', 'sceneIndex' => 2],
                ],
                'sceneItems' => [],
                'audioInputs' => [],
                'outputs' => [
                    'streaming' => false,
                    'recording' => false,
                    'recordingPaused' => false,
                    'virtualCam' => false,
                    'replayBuffer' => false,
                ],
                'stats' => [
                    'activeFps' => 60.0,
                    'cpuUsage' => 4.2,
                ],
            ],
        ];
    }

    public function online(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'online',
            'last_seen_at' => now(),
        ]);
    }
}
