<?php

namespace App\Models;

use Database\Factories\ObsMacroFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
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

    public function studio(): BelongsTo
    {
        return $this->belongsTo(ObsStudio::class, 'obs_studio_id');
    }
}
