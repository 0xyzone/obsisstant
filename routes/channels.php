<?php

use App\Models\ObsStudio;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id): bool {
    return (string) $user->id === (string) $id;
});

Broadcast::channel('obs.studio.{studioId}', function ($user, $studioId): bool {
    if ($user === null) {
        return false;
    }

    return ObsStudio::query()
        ->whereKey((int) $studioId)
        ->where('user_id', $user->id)
        ->exists();
});

Broadcast::channel('obs.user.{userId}.relay', function ($user, $userId): bool {
    return $user !== null && (string) $user->id === (string) $userId;
});

Broadcast::channel('obs.relay', function (): bool {
    return false;
});
