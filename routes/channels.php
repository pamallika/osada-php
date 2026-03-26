<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('event.{eventId}', function ($user, $eventId) {
    $event = \App\Models\Event::find($eventId);
    
    if (!$event) {
        return false;
    }

    return $user->guildMemberships()
        ->where('guild_id', $event->guild_id)
        ->where('status', 'active')
        ->exists();
});

Broadcast::channel('guild.{id}', function ($user, $id) {
    if (!$user) {
        return false;
    }

    $membership = $user->guildMemberships()
        ->where('guild_id', $id)
        ->where('status', 'active')
        ->first();

    if (!$membership) {
        return false;
    }

    return [
        'id' => $user->id,
        'name' => $user->name,
        'avatar' => $user->linkedAccounts()->where('provider', 'discord')->first()?->avatar,
        'role' => $membership->role,
    ];
});
