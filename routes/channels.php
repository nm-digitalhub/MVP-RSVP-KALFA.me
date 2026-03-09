<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('event.{id}', function ($user, $id) {
    $event = \App\Models\Event::find($id);
    if (! $event) {
        return false;
    }

    return $user->organizations->contains('id', $event->organization_id);
});
