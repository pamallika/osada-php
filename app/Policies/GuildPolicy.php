<?php

namespace App\Policies;

use App\Models\Guild;
use App\Models\User;

class GuildPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Guild $guild): bool
    {
        return $user->guildMemberships()
            ->where('guild_id', $guild->id)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Guild $guild): bool
    {
        return $user->guildMemberships()
            ->where('guild_id', $guild->id)
            ->whereIn('role', ['admin', 'creator', 'leader'])
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Determine whether the user can manage applications.
     */
    public function manageApplications(User $user, Guild $guild): bool
    {
        return $user->guildMemberships()
            ->where('guild_id', $guild->id)
            ->whereIn('role', ['admin', 'creator', 'leader'])
            ->where('status', 'active')
            ->exists();
    }

    public function manageEvents(User $user, Guild $guild): bool
    {
        return $user->guildMemberships()
            ->where('guild_id', $guild->id)
            ->whereIn('role', ['officer', 'admin', 'creator', 'leader'])
            ->where('status', 'active')
            ->exists();
    }

    public function createInvite(User $user, Guild $guild): bool
    {
        return $user->guildMemberships()
            ->where('guild_id', $guild->id)
            ->whereIn('role', ['officer', 'admin', 'creator', 'leader'])
            ->where('status', 'active')
            ->exists();
    }
}
