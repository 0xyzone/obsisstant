<?php

namespace App\Models;

use App\Models\Game;
use App\Models\Team;
use App\Models\MatchMaking;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tournament extends Model
{
    use HasFactory;

    /**
     * Get the game that owns the Tournament
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * Get all of the teams for the Tournament
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    /**
     * Get all of the matchMakings for the Tournament
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function matchMakings(): HasMany
    {
        return $this->hasMany(MatchMaking::class);
    }
}
