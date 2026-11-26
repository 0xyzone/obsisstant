<?php

namespace Database\Factories;

use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Package>
 */
class PackageFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucwords($name),
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'description' => fake()->sentence(),
            'price_cents' => 2900,
            'currency' => 'USD',
            'billing_cycle' => 'monthly',
            'features' => Package::defaultFeatures(),
            'limits' => Package::defaultLimits(),
            'is_active' => true,
            'sort_order' => 1,
        ];
    }

    public function unlimited(): static
    {
        return $this->state(fn (): array => [
            'features' => Package::defaultFeatures(),
            'limits' => [
                'max_studios' => null,
                'max_collections' => null,
                'max_views' => null,
                'max_macros' => null,
                'max_storage_mb' => null,
                'oxy_daily_messages' => null,
            ],
        ]);
    }
}
