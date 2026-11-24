<?php

namespace App\Models;

use Database\Factories\ScreenViewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'screen_collection_id',
    'user_id',
    'name',
    'slug',
    'sort_order',
    'background_mode',
    'background_color',
    'background_asset_id',
    'theme_override',
    'transition_override',
    'layers',
    'logic_rules',
])]
class ScreenView extends Model
{
    /** @use HasFactory<ScreenViewFactory> */
    use HasFactory;

    protected $table = 'obs_screen_views';

    protected static function booted(): void
    {
        static::creating(function (ScreenView $view): void {
            if ($view->user_id === null && $view->screen_collection_id) {
                $view->user_id = ScreenCollection::query()->whereKey($view->screen_collection_id)->value('user_id');
            }
            if (blank($view->slug)) {
                $view->slug = (Str::slug($view->name) ?: 'view').'-'.Str::lower(Str::random(4));
            }
            if ($view->layers === null) {
                $view->layers = [];
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'background_asset_id' => 'integer',
            'theme_override' => 'array',
            'transition_override' => 'array',
            'layers' => 'array',
            'logic_rules' => 'array',
        ];
    }

    /**
     * @param  Builder<ScreenView>  $query
     * @return Builder<ScreenView>
     */
    public function scopeOwnedBy(Builder $query, ?User $user): Builder
    {
        return $query->where('user_id', $user?->id ?? -1);
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(ScreenCollection::class, 'screen_collection_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function backgroundAsset(): BelongsTo
    {
        return $this->belongsTo(ScreenAsset::class, 'background_asset_id');
    }
}
