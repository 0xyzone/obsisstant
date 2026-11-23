<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'obs_studio_id',
    'user_id',
    'command_uuid',
    'request_type',
    'request_data',
    'status',
    'response_data',
    'error_message',
    'executed_at',
])]
class ObsCommandLog extends Model
{
    protected static function booted(): void
    {
        static::creating(function (ObsCommandLog $log): void {
            if (blank($log->command_uuid)) {
                $log->command_uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'request_data' => 'array',
            'response_data' => 'array',
            'executed_at' => 'datetime',
        ];
    }

    public function studio(): BelongsTo
    {
        return $this->belongsTo(ObsStudio::class, 'obs_studio_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
