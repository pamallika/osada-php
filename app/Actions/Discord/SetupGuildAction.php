<?php

namespace App\Actions\Discord;

use App\Models\Guild;

class SetupGuildAction
{
    public function execute(array $data): Guild
    {
        return Guild::query()->updateOrCreate(
            ['discord_id' => $data['discord_id']],
            array_filter([
                'name' => $data['name'] ?? null,
                'public_channel_id' => $data['public_channel_id'] ?? null,
                'admin_channel_id' => $data['admin_channel_id'] ?? null,
                'officer_role_ids' => $data['officer_role_ids'] ?? null,
            ])
        );
    }
}
