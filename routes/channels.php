<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id): bool {
    return (string) $user->id === (string) $id;
});

Broadcast::channel('obs.studio.{studioId}', function ($user): bool {
    return $user !== null;
});
