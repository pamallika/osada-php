<?php

namespace App\Actions\Guilds;

use App\Models\Guild;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class KickMemberAction
{
    private array $roles = [
        'member' => 1,
        'officer' => 2,
        'admin' => 3,
        'creator' => 4,
    ];

    /**
     * @param Guild $guild
     * @param User $actor
     * @param int $userIdToKick
     * @throws ValidationException
     */
    public function execute(Guild $guild, User $actor, int $userIdToKick): void
    {
        $actorMembership = $actor->guildMemberships()->where('guild_id', $guild->id)->firstOrFail();
        $targetMember = $guild->members()->where('user_id', $userIdToKick)->firstOrFail();

        $actorRoleWeight = $this->roles[$actorMembership->role] ?? 0;
        $targetRoleWeight = $this->roles[$targetMember->role] ?? 0;

        // admin (3) cannot kick creator (4) or another admin (3)
        // creator (4) can kick anyone but themselves (though they can't kick themselves via this logic easily)
        if ($actorRoleWeight <= $targetRoleWeight) {
            throw ValidationException::withMessages([
                'user_id' => ['You do not have permission to kick this member.'],
            ]);
        }

        if ($actor->id === $userIdToKick) {
            throw ValidationException::withMessages([
                'user_id' => ['You cannot kick yourself.'],
            ]);
        }

        $targetMember->delete();
    }
}
