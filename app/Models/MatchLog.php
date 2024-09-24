<?php

namespace App\Models;

use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MatchLog extends Model
{
    use HasFactory;

    /**
     * Get the tournament that owns the MatchLog
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    /**
     * Get the teamA that owns the MatchLog
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function teamA(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_a');
    }

    /**
     * Get the teamB that owns the MatchLog
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function teamB(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_b');
    }
}
