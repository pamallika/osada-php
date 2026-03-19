<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->guildMemberships()->where('status', 'active')->exists();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Event $event): bool
    {
        return $user->guildMemberships()
            ->where('guild_id', $event->guild_id)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Require checking against specific Guild via GuildPolicy instead.
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Event $event): bool
    {
        return $this->hasRoleInGuild($user, $event->guild_id, ['officer', 'admin', 'creator', 'leader']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Event $event): bool
    {
        return $this->hasRoleInGuild($user, $event->guild_id, ['admin', 'creator', 'leader']);
    }

    private function hasRoleInGuild(User $user, int $guildId, array $roles): bool
    {
        return $user->guildMemberships()
            ->where('guild_id', $guildId)
            ->whereIn('role', $roles)
            ->where('status', 'active')
            ->exists();
    }
}
