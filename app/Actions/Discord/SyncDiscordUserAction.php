<?php

namespace App\Actions\Discord;

use App\Models\User;

class SyncDiscordUserAction
{
    public function execute(array $data): User
    {
        return User::query()->updateOrCreate(
            ['discord_id' => $data['discord_id']],
            [
                'username' => $data['username'],
                'global_name' => $data['global_name'],
                'avatar' => $data['avatar'],
            ]
        );
    }
}
