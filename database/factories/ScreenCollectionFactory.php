<?php

namespace Database\Factories;

use App\Models\ObsStudio;
use App\Models\ScreenCollection;
use App\Models\User;
use App\Services\Obs\ScreenCollectionPresetService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ScreenCollection>
 */
class ScreenCollectionFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true).' Event';

        return [
            'user_id' => static fn (array $attributes): mixed => ! empty($attributes['obs_studio_id'])
                ? ObsStudio::query()->whereKey($attributes['obs_studio_id'])->value('user_id')
                : User::factory(),
            'obs_studio_id' => null,
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'public_token' => Str::random(32),
            'width' => 1920,
            'height' => 1080,
            'active_view_id' => null,
            'theme' => ScreenCollectionPresetService::defaultTheme(),
            'default_transition' => ScreenCollectionPresetService::defaultTransition(),
            'runtime_state' => ScreenCollectionPresetService::defaultRuntimeState(),
            'obs_scene_name' => 'Obsisstant Stage',
            'obs_source_name' => 'Obsisstant - Unified Screen',
        ];
    }
}
