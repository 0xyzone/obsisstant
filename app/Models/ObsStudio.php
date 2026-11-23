<?php

namespace App\Models;

use App\Events\ObsRelayCatalogUpdated;
use Database\Factories\ObsStudioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;

#[Fillable([
    'user_id',
    'name',
    'slug',
    'agent_token',
    'obs_host',
    'obs_port',
    'obs_password',
    'media_storage_path',
    'status',
    'is_active',
    'last_seen_at',
    'live_state',
])]
#[Hidden(['agent_token', 'obs_password'])]
class ObsStudio extends Model
{
    /** @use HasFactory<ObsStudioFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (ObsStudio $studio): void {
            if ($studio->user_id === null && Auth::check()) {
                $studio->user_id = Auth::id();
            }
            if (blank($studio->slug)) {
                $studio->slug = Str::slug($studio->name).'-'.Str::lower(Str::random(4));
            }
            if (blank($studio->agent_token)) {
                $studio->agent_token = 'obs_'.Str::random(48);
            }
        });

        static::created(function (ObsStudio $studio): void {
            try {
                event(new ObsRelayCatalogUpdated(
                    reason: 'created',
                    studioId: $studio->id,
                    userId: $studio->user_id,
                ));
            } catch (Throwable) {
                // Never block studio creation if Reverb server is offline
            }
        });

        static::updated(function (ObsStudio $studio): void {
            if ($studio->wasChanged(['name', 'obs_host', 'obs_port', 'obs_password', 'is_active'])) {
                try {
                    event(new ObsRelayCatalogUpdated(
                        reason: 'updated',
                        studioId: $studio->id,
                        userId: $studio->user_id,
                    ));
                } catch (Throwable) {
                    // Never block studio updates if Reverb server is offline
                }
            }
        });

        static::deleted(function (ObsStudio $studio): void {
            try {
                event(new ObsRelayCatalogUpdated(
                    reason: 'deleted',
                    studioId: $studio->id,
                    userId: $studio->user_id,
                ));
            } catch (Throwable) {
                // Never block studio deletion if Reverb server is offline
            }
        });
    }

    /**
     * @param  Builder<ObsStudio>  $query
     * @return Builder<ObsStudio>
     */
    public function scopeOwnedBy(Builder $query, ?User $user): Builder
    {
        return $query->where('user_id', $user?->id ?? -1);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'obs_password' => 'encrypted',
            'obs_port' => 'integer',
            'is_active' => 'boolean',
            'last_seen_at' => 'datetime',
            'live_state' => 'array',
        ];
    }

    public function screens(): HasMany
    {
        return $this->hasMany(ObsScreen::class);
    }

    public function macros(): HasMany
    {
        return $this->hasMany(ObsMacro::class);
    }

    public function commandLogs(): HasMany
    {
        return $this->hasMany(ObsCommandLog::class);
    }

    public function isOnline(): bool
    {
        if ($this->status === 'offline' || $this->last_seen_at === null) {
            return false;
        }

        return $this->last_seen_at->greaterThan(now()->subMinutes(2));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getScenes(): array
    {
        return $this->live_state['scenes'] ?? [];
    }

    public function getCurrentProgramScene(): ?string
    {
        return $this->live_state['currentProgramScene'] ?? null;
    }

    public function getCurrentPreviewScene(): ?string
    {
        return $this->live_state['currentPreviewScene'] ?? null;
    }

    public function isStudioModeEnabled(): bool
    {
        return (bool) ($this->live_state['studioModeEnabled'] ?? false);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getSceneItems(?string $sceneName = null): array
    {
        if ($sceneName !== null && $sceneName !== '') {
            $byScene = $this->live_state['sceneItemsByScene'] ?? [];
            if (is_array($byScene) && array_key_exists($sceneName, $byScene) && is_array($byScene[$sceneName])) {
                return $byScene[$sceneName];
            }
        }

        return $this->live_state['sceneItems'] ?? [];
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function getSceneItemsByScene(): array
    {
        $byScene = $this->live_state['sceneItemsByScene'] ?? [];

        return is_array($byScene) ? $byScene : [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAllInputs(): array
    {
        $inputs = $this->live_state['allInputs'] ?? [];

        return is_array($inputs) ? $inputs : [];
    }

    /**
     * @return array<int, string>
     */
    public function getInputKinds(): array
    {
        $kinds = $this->live_state['inputKinds'] ?? [];

        return is_array($kinds) ? array_values($kinds) : [];
    }

    /**
     * @return array<int, string>
     */
    public function getSceneCollections(): array
    {
        $collections = $this->live_state['sceneCollections'] ?? [];

        return is_array($collections) ? array_values($collections) : [];
    }

    public function getCurrentSceneCollection(): ?string
    {
        return $this->live_state['currentSceneCollection'] ?? null;
    }

    /**
     * @return array{width: int, height: int}
     */
    public function getVideoDimensions(): array
    {
        $dims = $this->live_state['videoDimensions'] ?? [];

        return [
            'width' => max(320, (int) ($dims['width'] ?? 1920)),
            'height' => max(180, (int) ($dims['height'] ?? 1080)),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAudioInputs(): array
    {
        return $this->live_state['audioInputs'] ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getOutputs(): array
    {
        return $this->live_state['outputs'] ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getStats(): array
    {
        return $this->live_state['stats'] ?? [];
    }
}
