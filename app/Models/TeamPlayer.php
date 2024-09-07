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
}
