<?php

namespace App\Actions\Guilds;

use App\Models\GuildInvite;
use App\Models\User;
use App\Models\GuildMember;
use App\Enums\GuildRole;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AcceptInviteAction
{
    public function execute(User $user, string $token): GuildMember
    {
        return DB::transaction(function () use ($user, $token) {
            $invite = GuildInvite::query()->where('token', $token)->lockForUpdate()->firstOrFail();

            if (!$invite->isValid()) {
                abort(400, 'This invite link is invalid, expired, or has reached its usage limit.');
            }

            $alreadyMember = GuildMember::query()->where('guild_id', $invite->guild_id)
                ->where('user_id', $user->id)
                ->exists();

            if ($alreadyMember) {
                abort(409, 'You are already a member of this guild.');
            }

            $member = GuildMember::query()->create([
                'guild_id' => $invite->guild_id,
                'user_id' => $user->id,
                'role' => GuildRole::MEMBER,
            ]);

            $invite->increment('uses');

            return $member;
        });
    }
}
