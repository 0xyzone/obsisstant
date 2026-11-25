<?php

namespace App\Models;

use App\Services\Obs\ScreenCollectionPresetService;
use Database\Factories\ScreenCollectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

#[Fillable([
    'user_id',
    'obs_studio_id',
    'name',
    'slug',
    'public_token',
    'width',
    'height',
    'active_view_id',
    'theme',
    'default_transition',
    'runtime_state',
    'deck_layout',
    'obs_scene_name',
    'obs_source_name',
])]
class ScreenCollection extends Model
{
    /** @use HasFactory<ScreenCollectionFactory> */
    use HasFactory;

    protected $table = 'obs_screen_collections';

    protected static function booted(): void
    {
        static::creating(function (ScreenCollection $collection): void {
            if ($collection->user_id === null) {
                $collection->user_id = $collection->obs_studio_id
                    ? ObsStudio::query()->whereKey($collection->obs_studio_id)->value('user_id')
                    : Auth::id();
            }
            if (blank($collection->slug)) {
                $baseSlug = Str::slug($collection->name) ?: 'collection';
                $collection->slug = $baseSlug.'-'.Str::lower(Str::random(5));
            }
            if (blank($collection->public_token)) {
                $collection->public_token = Str::random(32);
            }
            if ($collection->theme === null || $collection->theme === []) {
                $collection->theme = ScreenCollectionPresetService::defaultTheme();
            }
            if ($collection->default_transition === null || $collection->default_transition === []) {
                $collection->default_transition = ScreenCollectionPresetService::defaultTransition();
            }
            if ($collection->runtime_state === null || $collection->runtime_state === []) {
                $collection->runtime_state = ScreenCollectionPresetService::defaultRuntimeState();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'width' => 'integer',
            'height' => 'integer',
            'active_view_id' => 'integer',
            'theme' => 'array',
            'default_transition' => 'array',
            'runtime_state' => 'array',
            'deck_layout' => 'array',
        ];
    }

    /**
     * @param  Builder<ScreenCollection>  $query
     * @return Builder<ScreenCollection>
     */
    public function scopeOwnedBy(Builder $query, ?User $user): Builder
    {
        return $query->where('user_id', $user?->id ?? -1);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function studio(): BelongsTo
    {
        return $this->belongsTo(ObsStudio::class, 'obs_studio_id');
    }

    public function views(): HasMany
    {
        return $this->hasMany(ScreenView::class, 'screen_collection_id')->orderBy('sort_order')->orderBy('id');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(ScreenAsset::class, 'screen_collection_id')->latest('id');
    }

    public function activeView(): BelongsTo
    {
        return $this->belongsTo(ScreenView::class, 'active_view_id');
    }

    public function getStageUrl(bool $absolute = true): string
    {
        return route('obs.collection.show', ['token' => $this->public_token], $absolute);
    }

    /**
     * @return array<string, mixed>
     */
    public function getMergedTheme(): array
    {
        return array_merge(ScreenCollectionPresetService::defaultTheme(), $this->theme ?? []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getMergedTransition(): array
    {
        return array_merge(ScreenCollectionPresetService::defaultTransition(), $this->default_transition ?? []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getMergedRuntimeState(): array
    {
        $defaults = ScreenCollectionPresetService::defaultRuntimeState();
        $saved = $this->runtime_state ?? [];

        return [
            'timer' => array_merge($defaults['timer'], is_array($saved['timer'] ?? null) ? $saved['timer'] : []),
            'scoreboard' => array_merge($defaults['scoreboard'], is_array($saved['scoreboard'] ?? null) ? $saved['scoreboard'] : []),
            'lower_third' => array_merge($defaults['lower_third'], is_array($saved['lower_third'] ?? null) ? $saved['lower_third'] : []),
            'ticker' => array_merge($defaults['ticker'], is_array($saved['ticker'] ?? null) ? $saved['ticker'] : []),
            'alert' => array_merge($defaults['alert'], is_array($saved['alert'] ?? null) ? $saved['alert'] : []),
            'hidden_layers' => array_values(is_array($saved['hidden_layers'] ?? null) ? $saved['hidden_layers'] : []),
            'last_transition' => array_merge($defaults['last_transition'], is_array($saved['last_transition'] ?? null) ? $saved['last_transition'] : []),
        ];
    }

    /**
     * Build the configurable Mobile Deck tile layout, falling back to auto-generated tiles for every app view.
     *
     * @return array{columns: int, tiles: array<int, array<string, mixed>>}
     */
    public function getNormalizedDeckLayout(): array
    {
        $saved = $this->deck_layout;
        if (is_array($saved) && array_key_exists('tiles', $saved) && is_array($saved['tiles'])) {
            $columns = max(0, min(6, (int) ($saved['columns'] ?? 0)));

            return [
                'columns' => $columns,
                'tiles' => array_values($saved['tiles']),
            ];
        }

        $this->loadMissing('views');
        $tiles = [];

        foreach ($this->views as $view) {
            $tiles[] = [
                'id' => 'tile_view_'.$view->id,
                'type' => 'switch_view',
                'label' => $view->name,
                'subtitle' => '',
                'color' => 'neutral',
                'span' => 1,
                'config' => [
                    'view_id' => $view->id,
                    'transition_type' => 'default',
                ],
            ];
        }

        $tiles[] = [
            'id' => 'tile_timer_toggle',
            'type' => 'timer',
            'label' => 'Countdown Timer',
            'subtitle' => 'Live stage timer',
            'color' => 'gold',
            'span' => 1,
            'config' => [
                'action' => 'toggle',
            ],
        ];

        $tiles[] = [
            'id' => 'tile_l3_toggle',
            'type' => 'lower_third',
            'label' => 'Lower Third',
            'subtitle' => 'Toggle banner',
            'color' => 'neutral',
            'span' => 1,
            'config' => [
                'action' => 'toggle',
            ],
        ];

        $tiles[] = [
            'id' => 'tile_push_obs',
            'type' => 'obs_action',
            'label' => 'Push to OBS',
            'subtitle' => 'Sync 1 browser source',
            'color' => 'neutral',
            'span' => 1,
            'config' => [
                'action' => 'push_stage',
            ],
        ];

        return [
            'columns' => 0,
            'tiles' => $tiles,
        ];
    }
}
