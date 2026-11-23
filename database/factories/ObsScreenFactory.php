<?php

namespace Database\Factories;

use App\Models\ObsScreen;
use App\Services\Obs\ObsScreenPresetService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ObsScreen>
 */
class ObsScreenFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true).' Screen';
        $type = 'lower_thirds';

        return [
            'obs_studio_id' => null,
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'public_token' => Str::random(32),
            'template_type' => $type,
            'width' => 1920,
            'height' => 1080,
            'theme' => ObsScreenPresetService::defaultTheme($type),
            'state' => ObsScreenPresetService::defaultState($type),
            'obs_scene_name' => 'Main Scene',
            'obs_source_name' => "Obsisstant - {$name}",
            'is_live' => true,
        ];
    }
}
