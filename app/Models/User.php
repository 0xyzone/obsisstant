<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable(['name', 'username', 'email', 'contact_number', 'current_address', 'password', 'relay_token', 'media_storage_path'])]
#[Hidden(['password', 'remember_token', 'relay_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if (blank($user->relay_token)) {
                $user->relay_token = 'usr_'.Str::random(48);
            }
        });
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'admin';
    }

    public function obsStudios(): HasMany
    {
        return $this->hasMany(ObsStudio::class);
    }

    public function obsScreens(): HasMany
    {
        return $this->hasMany(ObsScreen::class);
    }

    public function obsMacros(): HasMany
    {
        return $this->hasMany(ObsMacro::class);
    }

    public function obsCommandLogs(): HasMany
    {
        return $this->hasMany(ObsCommandLog::class);
    }

    public function getOrCreateRelayToken(): string
    {
        if (blank($this->relay_token)) {
            $this->forceFill(['relay_token' => 'usr_'.Str::random(48)])->save();
        }

        return (string) $this->relay_token;
    }

    public function rotateRelayToken(): string
    {
        $token = 'usr_'.Str::random(48);
        $this->forceFill(['relay_token' => $token])->save();

        return $token;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
