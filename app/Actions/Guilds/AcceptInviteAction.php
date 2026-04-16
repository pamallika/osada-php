<?php

namespace App\Actions\Guilds;

use App\Models\GuildInvite;
use App\Models\User;
use App\Enums\GuildRole;
use Illuminate\Validation\ValidationException;

class AcceptInviteAction
{
    /**
     * @param User $user
     * @param string $token
     * @return void
     * @throws ValidationException
     */
    public function execute(User $user, string $token): void
    {
        $invite = GuildInvite::where('token', $token)->first();

        if (!$invite || !$invite->isValid()) {
            throw ValidationException::withMessages([
                'invite' => ['The invite is invalid or expired.'],
            ]);
        }

        // Check if user is already in a guild (any status)
        if ($user->guildMemberships()->exists()) {
            throw ValidationException::withMessages([
                'invite' => ['You are already a member or have a pending application in a guild.'],
            ]);
        }

        $member = $invite->guild->members()->withTrashed()->firstOrNew([
            'user_id' => $user->id,
        ]);

        $member->fill([
            'role' => GuildRole::MEMBER,
            'status' => 'pending',
            'joined_at' => now(),
        ]);

        $member->save();

        if ($member->trashed()) {
            $member->restore();
        }

        $invite->increment('uses');
    }
}
