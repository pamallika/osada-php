<?php

namespace App\Actions\Guilds;

use App\Models\Guild;
use App\Models\GuildMember;
use App\Models\User; // Added this line
use App\Enums\GuildRole;
use Illuminate\Validation\ValidationException;

class UpdateMemberRoleAction
{
    public function execute(Guild $guild, User $targetUser, string $newRole): void
    {
        $member = $guild->members()->where('user_id', $targetUser->id)->firstOrFail();

        $member->update([
            'role' => $newRole
        ]);

        $targetUser->tokens()->delete();
    }
}
