<?php

namespace App\Actions\Guilds;

use App\Models\Guild;
use App\Models\GuildMember;

class ApproveApplicationAction
{
    public function execute(Guild $guild, int $userId): void
    {
        $member = $guild->members()->where('user_id', $userId)->where('status', 'pending')->firstOrFail();

        $member->update([
            'status' => 'active'
        ]);
    }
}
