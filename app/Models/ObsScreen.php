<?php

namespace App\Models;

use App\Services\Obs\ObsScreenPresetService;
use Database\Factories\ObsScreenFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
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
