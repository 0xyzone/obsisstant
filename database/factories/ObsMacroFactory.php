<?php

namespace Database\Factories;

use App\Models\ObsMacro;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ObsMacro>
 */
class ObsMacroFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true).' Macro';

        return [
            'obs_studio_id' => null,
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'icon' => 'heroicon-o-bolt',
            'color' => 'gold',
            'description' => fake()->sentence(),
            'steps' => [
                [
                    'type' => 'obs_command',
                    'request_type' => 'SetCurrentProgramScene',
                    'request_data' => ['sceneName' => 'Main Scene'],
                ],
            ],
            'sort_order' => 0,
        ];
    }
}
