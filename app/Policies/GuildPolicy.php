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

    /**
     * Determine whether the user can manage guild members.
     */
    public function manageMembers(User $user, Guild $guild, User $targetUser): bool
    {
        $currentMember = $user->guildMemberships()
            ->where('guild_id', $guild->id)
            ->where('status', 'active')
            ->first();

        if (!$currentMember) {
            return false;
        }

        $targetMember = $targetUser->guildMemberships()
            ->where('guild_id', $guild->id)
            ->where('status', 'active')
            ->first();

        if (!$targetMember) {
            return false;
        }

        // Creator can manage anyone
        if ($currentMember->role === 'creator') {
            return true;
        }

        // Admin can manage anyone except the creator
        if ($currentMember->role === 'admin') {
            return $targetMember->role !== 'creator';
        }

        return false;
    }

    /**
     * Determine whether the user can assign a specific role.
     */
    public function assignRole(User $user, Guild $guild, string $newRole): bool
    {
        \Illuminate\Support\Facades\Log::info("Role attempt: [{$newRole}] for guild [{$guild->id}] by User [{$user->id}]");

        $currentMember = $user->guildMemberships()
            ->where('guild_id', $guild->id)
            ->where('status', 'active')
            ->first();

        if (!$currentMember) {
            \Illuminate\Support\Facades\Log::warning("Policy failed: user [{$user->id}] not in guild [{$guild->id}]");
            return false;
        }

        // Creator can assign any role
        if ($currentMember->role === 'creator') {
            return true;
        }

        // Admin can only assign officer or member
        if ($currentMember->role === 'admin') {
             if ($newRole === 'admin') {
                \Illuminate\Support\Facades\Log::warning("Admin [{$user->id}] attempted to assign Admin role - DENIED");
                return false;
             }
            return in_array($newRole, ['officer', 'member']);
        }


        return false;
    }
}



