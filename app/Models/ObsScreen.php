<?php

namespace App\Models;

use App\Services\Obs\ObsScreenPresetService;
use Database\Factories\ObsScreenFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

#[Fillable([
    'user_id',
    'obs_studio_id',
    'name',
    'slug',
    'public_token',
    'template_type',
    'width',
    'height',
    'theme',
    'state',
    'obs_scene_name',
    'obs_source_name',
    'is_live',
])]
class ObsScreen extends Model
{
    /** @use HasFactory<ObsScreenFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (ObsScreen $screen): void {
            if ($screen->user_id === null) {
                $screen->user_id = $screen->obs_studio_id
                    ? ObsStudio::query()->whereKey($screen->obs_studio_id)->value('user_id')
                    : Auth::id();
            }
            if (blank($screen->slug)) {
                $screen->slug = Str::slug($screen->name).'-'.Str::lower(Str::random(4));
            }
            if (blank($screen->public_token)) {
                $screen->public_token = Str::random(32);
            }
            if (blank($screen->obs_source_name)) {
                $screen->obs_source_name = 'Obsisstant - '.$screen->name;
            }
            if ($screen->theme === null || $screen->theme === []) {
                $screen->theme = ObsScreenPresetService::defaultTheme($screen->template_type ?: 'lower_thirds');
            }
            if ($screen->state === null || $screen->state === []) {
                $screen->state = ObsScreenPresetService::defaultState($screen->template_type ?: 'lower_thirds');
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
            'theme' => 'array',
            'state' => 'array',
            'is_live' => 'boolean',
        ];
    }

    /**
     * @param  Builder<ObsScreen>  $query
     * @return Builder<ObsScreen>
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

    public function getOverlayUrl(): string
    {
        return route('obs.screen.show', ['token' => $this->public_token]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getMergedTheme(): array
    {
        return array_merge(
            ObsScreenPresetService::defaultTheme($this->template_type),
            $this->theme ?? [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getMergedState(): array
    {
        return array_merge(
            ObsScreenPresetService::defaultState($this->template_type),
            $this->state ?? [],
        );
    }
}
