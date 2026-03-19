<?php

namespace App\Actions\Guilds;

use App\Models\Guild;
use App\Models\GuildMember;
use App\Enums\GuildRole;
use Illuminate\Validation\ValidationException;

class UpdateMemberRoleAction
{
    public function execute(Guild $guild, int $userId, string $newRole): void
    {
        $member = $guild->members()->where('user_id', $userId)->firstOrFail();

        if ($member->role === GuildRole::CREATOR) {
            throw ValidationException::withMessages([
                'role' => ['Cannot change the role of a creator.'],
            ]);
        }

        if (!in_array($newRole, [GuildRole::MEMBER, GuildRole::OFFICER, GuildRole::ADMIN])) {
             throw ValidationException::withMessages([
                'role' => ['Invalid role.'],
            ]);
        }

        $member->update([
            'role' => $newRole
        ]);
    }
}
