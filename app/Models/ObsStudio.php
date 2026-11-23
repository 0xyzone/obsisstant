<?php

namespace App\Models;

use Database\Factories\ObsStudioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'name',
    'slug',
    'agent_token',
    'obs_host',
    'obs_port',
    'obs_password',
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
            if (blank($studio->slug)) {
                $studio->slug = Str::slug($studio->name).'-'.Str::lower(Str::random(4));
            }
            if (blank($studio->agent_token)) {
                $studio->agent_token = 'obs_'.Str::random(48);
            }
        });
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
    public function getSceneItems(): array
    {
        return $this->live_state['sceneItems'] ?? [];
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
