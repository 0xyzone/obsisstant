<?php

namespace App\Models;

use App\Models\GroupTeams;
use App\Models\Tournament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Group extends Model
{
    use HasFactory;

    /**
     * Get the tournament that owns the Group
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    /**
     * Get all of the groupTeams for the Group
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function groupTeams(): HasMany
    {
        return $this->hasMany(GroupTeams::class);
    }
}
