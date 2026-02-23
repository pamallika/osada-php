<?php

namespace App\Actions\Guilds;

use App\Models\Guild;
use App\Models\GuildInvite;
use App\Models\User;
use Illuminate\Support\Str;

class GenerateInviteAction
{
    public function execute(Guild $guild, User $creator, array $data): GuildInvite
    {
        $token = Str::random(16);

        return $guild->invites()->create([
            'token' => $token,
            'max_uses' => $data['max_uses'] ?? null,
            'expires_at' => isset($data['expires_in_days'])
                ? now()->addDays($data['expires_in_days'])
                : null,
            'created_by' => $creator->id,
        ]);
    }
}
