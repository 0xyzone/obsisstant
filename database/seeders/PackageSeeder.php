<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'slug' => 'starter',
                'name' => 'Starter',
                'description' => 'Essential OBS remote control and stage overlays for solo creators.',
                'price_cents' => 150000,
                'currency' => 'NPR',
                'billing_cycle' => 'monthly',
                'sort_order' => 1,
                'features' => [
                    'obs_control' => true,
                    'unified_stage' => true,
                    'canvas_builder' => false,
                    'mobile_deck' => true,
                    'macros' => true,
                    'audio_mixer' => true,
                    'oxy_gemini_ai' => false,
                ],
                'limits' => [
                    'max_studios' => 1,
                    'max_collections' => 2,
                    'max_views' => 6,
                    'max_macros' => 5,
                    'max_storage_mb' => 100,
                    'oxy_daily_messages' => 0,
                ],
            ],
            [
                'slug' => 'pro-studio',
                'name' => 'Pro Studio',
                'description' => 'Full Canvas Builder, Unified Stage, Macros, and Oxy Gemini AI.',
                'price_cents' => 350000,
                'currency' => 'NPR',
                'billing_cycle' => 'monthly',
                'sort_order' => 2,
                'features' => Package::defaultFeatures(),
                'limits' => [
                    'max_studios' => 3,
                    'max_collections' => 8,
                    'max_views' => 30,
                    'max_macros' => 25,
                    'max_storage_mb' => 500,
                    'oxy_daily_messages' => 100,
                ],
            ],
            [
                'slug' => 'agency-unlimited',
                'name' => 'Agency Unlimited',
                'description' => 'Multi-rig broadcast control with unlimited studios, views, and priority AI.',
                'price_cents' => 850000,
                'currency' => 'NPR',
                'billing_cycle' => 'monthly',
                'all_features' => true,
                'sort_order' => 3,
                'features' => Package::defaultFeatures(),
                'limits' => [
                    'max_studios' => null,
                    'max_collections' => null,
                    'max_views' => null,
                    'max_macros' => null,
                    'max_storage_mb' => 5000,
                    'oxy_daily_messages' => null,
                ],
            ],
            [
                'slug' => 'lifetime-founder',
                'name' => 'Lifetime All-Access',
                'description' => 'One-time purchase with never-expiring access to all current and upcoming features.',
                'price_cents' => 2500000,
                'currency' => 'NPR',
                'billing_cycle' => 'lifetime',
                'all_features' => true,
                'sort_order' => 4,
                'features' => Package::defaultFeatures(),
                'limits' => [
                    'max_studios' => null,
                    'max_collections' => null,
                    'max_views' => null,
                    'max_macros' => null,
                    'max_storage_mb' => null,
                    'oxy_daily_messages' => null,
                ],
            ],
        ];

        foreach ($plans as $plan) {
            Package::query()->updateOrCreate(
                ['slug' => $plan['slug']],
                [...$plan, 'is_active' => true],
            );
        }
    }
}
