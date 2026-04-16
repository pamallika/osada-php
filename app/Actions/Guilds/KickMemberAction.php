<?php

namespace App\Actions\Guilds;

use App\Models\Guild;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class KickMemberAction
{
    public function execute(Guild $guild, User $targetUser): void
    {
        $targetMember = $guild->members()->where('user_id', $targetUser->id)->where('status', 'active')->firstOrFail();

        $targetMember->delete();

        $guild->membershipHistories()->create([
            'user_id' => $targetUser->id,
            'action' => 'kick',
        ]);

        $targetUser->tokens()->delete();
    }
}

