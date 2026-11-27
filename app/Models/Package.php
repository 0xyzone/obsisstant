<?php

namespace App\Models;

use Database\Factories\PackageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'slug',
    'description',
    'price_cents',
    'currency',
    'billing_cycle',
    'all_features',
    'features',
    'limits',
    'is_active',
    'sort_order',
])]
class Package extends Model
{
    /** @use HasFactory<PackageFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    public static function featureCatalog(): array
    {
        return [
            'obs_control' => 'OBS Studio Control & Commands',
            'unified_stage' => 'Unified Stage & Browser Source',
            'canvas_builder' => 'Visual Canvas Builder',
            'mobile_deck' => 'Mobile Control Deck',
            'macros' => 'Multi-Step Macros',
            'audio_mixer' => 'Live Audio Mixer',
            'oxy_gemini_ai' => 'Oxy Gemini AI Assistant',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function limitCatalog(): array
    {
        return [
            'max_studios' => 'Max OBS Studios',
            'max_collections' => 'Max Screen Collections',
            'max_views' => 'Max Stage Views',
            'max_macros' => 'Max OBS Macros',
            'max_storage_mb' => 'Media Storage Quota (MB)',
            'oxy_daily_messages' => 'Oxy AI Messages / Day',
        ];
    }

    /**
     * @return array<string, bool>
     */
    public static function defaultFeatures(): array
    {
        return [
            'obs_control' => true,
            'unified_stage' => true,
            'canvas_builder' => true,
            'mobile_deck' => true,
            'macros' => true,
            'audio_mixer' => true,
            'oxy_gemini_ai' => true,
        ];
    }

    /**
     * @return array<string, int|null>
     */
    public static function defaultLimits(): array
    {
        return [
            'max_studios' => 3,
            'max_collections' => 5,
            'max_views' => 25,
            'max_macros' => 20,
            'max_storage_mb' => 500,
            'oxy_daily_messages' => 100,
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function formattedPrice(): string
    {
        $cents = (int) $this->price_cents;
        $whole = $cents / 100;
        $amount = ($cents % 100 === 0)
            ? number_format($whole, 0)
            : number_format($whole, 2);
        $currency = $this->currency ?: 'NPR';
        $cycle = match ($this->billing_cycle) {
            'yearly' => '/yr',
            'lifetime' => ' one-time',
            default => '/mo',
        };

        return "{$currency} {$amount}{$cycle}";
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'all_features' => 'boolean',
            'features' => 'array',
            'limits' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
