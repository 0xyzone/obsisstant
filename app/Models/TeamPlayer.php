<?php

namespace App\Models;

use App\Models\Hero;
use App\Models\Team;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TeamPlayer extends Model
{
    use HasFactory;

    /**
     * Get the team that owns the TeamPlayer
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the hero that owns the TeamPlayer
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function hero(): BelongsTo
    {
        return $this->belongsTo(Hero::class);
    }

    protected $hidden = [
        'player_image_path',
        'hero_id',
        'hero'
    ];

    protected $appends = [
        'player_image_url',
        'heroDetail'
    ];

    public function getPlayerImageUrlAttribute()
    {
        return $this->player_image_path ? url('storage/' . $this->player_image_path) : null;
    }

    public function getHeroDetailAttribute()
    {
        return $this->hero ?? null;
    }
}
