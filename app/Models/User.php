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
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable(['name', 'username', 'email', 'is_super_admin', 'contact_number', 'current_address', 'password', 'relay_token', 'media_storage_path'])]
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
        if ($panel->getId() === 'superadmin') {
            return (bool) $this->is_super_admin;
        }

        return $panel->getId() === 'admin';
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function latestSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany('id');
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

    public function screenCollections(): HasMany
    {
        return $this->hasMany(ScreenCollection::class);
    }

    public function screenAssets(): HasMany
    {
        return $this->hasMany(ScreenAsset::class);
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
            'is_super_admin' => 'boolean',
            'password' => 'hashed',
        ];
    }
}
