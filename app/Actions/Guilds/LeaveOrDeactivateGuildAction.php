<?php

namespace App\Actions\Guilds;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class LeaveOrDeactivateGuildAction
{
    /**
     * @param User $user
     * @return User
     * @throws ValidationException
     */
    public function execute(User $user): User
    {
        $membership = $user->guildMemberships()
            ->where('status', 'active')
            ->first();

        if (!$membership) {
            throw ValidationException::withMessages([
                'guild' => ['You are not a member of any guild.'],
            ]);
        }

        $guild = $membership->guild;

        if ($membership->role === 'creator') {
            // Creator leaves: deactivates the guild
            $guild->update(['status' => 'inactive']);
            
            // Optionally remove ALL members or just keep them as inactive?
            // PRD says "Remove the guild_membership record" for members. 
            // For creator, it says "Set guild status = 'inactive'".
            // We'll keep the creator's membership but the guild is now inactive.
            // Actually, usually when a guild is inactive, memberships should probably be cleared or ignored.
            // To follow "Returns: User without guild", we should probably remove the membership too.
            $membership->delete();
        } else {
            // Regular member leaves
            $membership->delete();
        }

        // event_participants records are NOT deleted (preserves history).

        return $user->load(['profile', 'linked_accounts', 'guildMemberships']);
    }
}
