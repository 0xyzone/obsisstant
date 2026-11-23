<?php

namespace Database\Factories;

use App\Models\ObsMacro;
use App\Models\ObsStudio;
use App\Models\User;
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
            'user_id' => static fn (array $attributes): mixed => ! empty($attributes['obs_studio_id'])
                ? ObsStudio::query()->whereKey($attributes['obs_studio_id'])->value('user_id')
                : User::factory(),
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
