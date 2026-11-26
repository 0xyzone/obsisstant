<?php

namespace Database\Factories;

use App\Models\Package;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    public function configure(): static
    {
        return $this->afterCreating(function (User $user): void {
            if ($user->is_super_admin || $user->subscriptions()->exists()) {
                return;
            }

            $package = Package::query()->where('slug', 'pro-studio')->first()
                ?? Package::factory()->unlimited()->create([
                    'name' => 'Pro Studio',
                    'slug' => 'pro-studio',
                ]);

            Subscription::query()->create([
                'user_id' => $user->id,
                'package_id' => $package->id,
                'status' => Subscription::STATUS_ACTIVE,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addYear(),
            ]);
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'is_super_admin' => false,
            'contact_number' => fake()->unique()->numerify('98########'),
            'current_address' => fake()->address(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function superAdmin(): static
    {
        return $this->state(fn (): array => [
            'is_super_admin' => true,
        ]);
    }

    public function withoutSubscription(): static
    {
        return $this->afterCreating(function (User $user): void {
            $user->subscriptions()->delete();
        });
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
