<?php

namespace App\Models;

use Database\Factories\ObsMacroFactory;
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
    'icon',
    'color',
    'description',
    'steps',
    'sort_order',
])]
class ObsMacro extends Model
{
    /** @use HasFactory<ObsMacroFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (ObsMacro $macro): void {
            if ($macro->user_id === null) {
                $macro->user_id = $macro->obs_studio_id
                    ? ObsStudio::query()->whereKey($macro->obs_studio_id)->value('user_id')
                    : Auth::id();
            }
            if (blank($macro->slug)) {
                $macro->slug = Str::slug($macro->name).'-'.Str::lower(Str::random(4));
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'steps' => 'array',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<ObsMacro>  $query
     * @return Builder<ObsMacro>
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
}
