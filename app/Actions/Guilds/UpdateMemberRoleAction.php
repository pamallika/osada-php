<?php

namespace App\Actions\Guilds;

use App\Models\Guild;
use App\Models\User;

class UpdateMemberRoleAction
{
    public function execute(Guild $guild, User $targetUser, string $newRole): void
    {
        $member = $guild->members()->where('user_id', $targetUser->id)->firstOrFail();

        $member->update([
            'role' => $newRole
        ]);
    }
}
