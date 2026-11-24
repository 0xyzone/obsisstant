<?php

namespace Database\Factories;

use App\Models\ScreenAsset;
use App\Models\ScreenCollection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScreenAsset>
 */
class ScreenAssetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => static fn (array $attributes): mixed => ! empty($attributes['screen_collection_id'])
                ? ScreenCollection::query()->whereKey($attributes['screen_collection_id'])->value('user_id')
                : User::factory(),
            'screen_collection_id' => null,
            'name' => fake()->words(2, true).' Asset',
            'type' => 'image',
            'disk' => 'public',
            'path' => 'screen-assets/sample.png',
            'mime_type' => 'image/png',
            'size_bytes' => 1024,
            'original_filename' => 'sample.png',
        ];
    }
}
