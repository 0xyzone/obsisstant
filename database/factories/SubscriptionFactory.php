<?php

namespace Database\Factories;

use App\Models\Package;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'package_id' => Package::factory(),
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'feature_overrides' => null,
            'limit_overrides' => null,
            'notes' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'status' => Subscription::STATUS_EXPIRED,
            'ends_at' => now()->subDay(),
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (): array => [
            'status' => Subscription::STATUS_SUSPENDED,
        ]);
    }
}
