<?php

namespace App\Models;

use App\Models\GroupTeams;
use App\Models\Tournament;
use App\Models\MatchMaking;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Team extends Model
{
    use HasFactory;

    /**
     * Get the tournament that owns the Team
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    /**
     * Get all of the teamPlayers for the Team
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function teamPlayers(): HasMany
    {
        return $this->hasMany(TeamPlayer::class);
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

    protected $appends = [
        'team_logo_url'
    ];

    public function getTeamLogoUrlAttribute()
    {
        return $this->team_logo_path ? url('storage/' . $this->team_logo_path) : null;
    }

    /**
     * Get all of the groupTeams for the Team
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function groupTeams(): HasMany
    {
        return $this->hasMany(GroupTeams::class);
    }
}
