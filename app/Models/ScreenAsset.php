<?php

namespace App\Models;

use Database\Factories\ScreenAssetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

#[Fillable([
    'user_id',
    'screen_collection_id',
    'name',
    'type',
    'disk',
    'path',
    'mime_type',
    'size_bytes',
    'original_filename',
])]
class ScreenAsset extends Model
{
    /** @use HasFactory<ScreenAssetFactory> */
    use HasFactory;

    protected $table = 'obs_screen_assets';

    protected static function booted(): void
    {
        static::creating(function (ScreenAsset $asset): void {
            if ($asset->user_id === null) {
                $asset->user_id = $asset->screen_collection_id
                    ? ScreenCollection::query()->whereKey($asset->screen_collection_id)->value('user_id')
                    : Auth::id();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
        ];
    }

    /**
     * @param  Builder<ScreenAsset>  $query
     * @return Builder<ScreenAsset>
     */
    public function scopeOwnedBy(Builder $query, ?User $user): Builder
    {
        return $query->where('user_id', $user?->id ?? -1);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(ScreenCollection::class, 'screen_collection_id');
    }

    public function getUrl(bool $absolute = false): string
    {
        $filename = $this->original_filename ?: basename($this->path);

        return route('obs.screen-asset.show', [
            'asset' => $this->id,
            'filename' => $filename,
        ], $absolute);
    }
}
