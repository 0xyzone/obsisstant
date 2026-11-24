<?php

namespace Database\Factories;

use App\Models\ScreenCollection;
use App\Models\ScreenView;
use App\Services\Obs\ScreenCollectionPresetService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ScreenView>
 */
class ScreenViewFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true).' View';

        return [
            'screen_collection_id' => ScreenCollection::factory(),
            'user_id' => static fn (array $attributes): mixed => ScreenCollection::query()
                ->whereKey($attributes['screen_collection_id'])
                ->value('user_id'),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'sort_order' => 1,
            'background_mode' => 'theme',
            'background_color' => null,
            'background_asset_id' => null,
            'theme_override' => null,
            'transition_override' => null,
            'layers' => [
                ScreenCollectionPresetService::makeDefaultLayer('text', $name, ['text' => $name]),
            ],
            'logic_rules' => ['on_timer_end_action' => 'none'],
        ];
    }
}
