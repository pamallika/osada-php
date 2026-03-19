<?php

namespace App\Actions\Discord;

use App\Models\Guild;
use App\Models\GuildIntegration;

class SyncGuildAction
{
    /**
     * Sync guild data from Discord bot.
     */
    public function execute(array $data): Guild
    {
        $integration = GuildIntegration::where('provider', 'discord')
            ->where('platform_id', $data['discord_id'])
            ->firstOrFail();

        $guild = $integration->guild;
        if (isset($data['name'])) {
            $guild->update(['name' => $data['name']]);
        }

        $settings = $integration->settings ?? [];
        if (isset($data['public_channel_id'])) $settings['public_channel_id'] = $data['public_channel_id'];
        if (isset($data['admin_channel_id'])) $settings['admin_channel_id'] = $data['admin_channel_id'];
        if (isset($data['officer_role_ids'])) $settings['officer_role_ids'] = $data['officer_role_ids'];

        $integration->settings = $settings;
        if (isset($data['name'])) $integration->platform_title = $data['name'];
        $integration->save();

        return $guild;
    }
}
