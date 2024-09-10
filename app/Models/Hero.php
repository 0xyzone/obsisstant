<?php

namespace App\Models;

use App\Models\Game;
use App\Models\TeamPlayer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Hero extends Model
{
    use HasFactory;

    /**
     * Get all of the teamPlayers for the Hero
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function teamPlayers(): HasMany
    {
        return $this->hasMany(TeamPlayer::class);
    }

    /**
     * Get the game that owns the Hero
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    protected $hidden = [
        'hero_image_path'
    ];

    protected $appends = [
        'hero_image_url'
    ];

    public function getHeroImageUrlAttribute()
    {
        return $this->hero_image_path ? url('storage/' . $this->hero->hero_image_path) : null;
    }
}
