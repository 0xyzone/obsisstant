<?php

namespace App\Models;

use App\Models\Tournament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Game extends Model
{
    use HasFactory;

    /**
     * Get all of the tournaments for the Game
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tournaments(): HasMany
    {
        return $this->hasMany(Tournament::class);
    }

    public $appends = [
        "game_logo_url",
    ];

    public function getGameLogoUrlAttribute() {
        return $this->game_logo_path ? url('storage/' . $this->game_logo_path) : null;
    }
}
