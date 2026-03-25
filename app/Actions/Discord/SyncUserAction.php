<?php

namespace App\Actions\Discord;

use App\Models\User;
use App\Models\LinkedAccount;
use Illuminate\Support\Facades\DB;

class SyncUserAction
{
    /**
     * Sync user data from Discord bot.
     * 
     * @param array{discord_id: string, username: string, global_name: string, avatar: string, discord_guild_id?: string} $data
     * @return User
     */
    public function execute(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $linkedAccount = LinkedAccount::where('provider', 'discord')
                ->where('provider_id', $data['discord_id'])
                ->first();

            if ($linkedAccount) {
                $linkedAccount->update([
                    'username' => $data['username'],
                    'display_name' => $data['global_name'],
                    'avatar' => $data['avatar'],
                ]);
                $user = $linkedAccount->user;
            } else {
                $user = User::query()->create();

                $user->linkedAccounts()->create([
                    'provider' => 'discord',
                    'provider_id' => $data['discord_id'],
                    'username' => $data['username'],
                    'display_name' => $data['global_name'],
                    'avatar' => $data['avatar'],
                ]);
            }

            // Актуализация Global Name в профиле
            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'global_name' => $data['global_name'],
                    'family_name' => $user->profile?->family_name ?? '',
                    'char_class' => $user->profile?->char_class ?? 'None',
                ]
            );

            // Авто-привязка к гильдии, если передан discord_guild_id
            if (!empty($data['discord_guild_id'])) {
                $integration = \App\Models\GuildIntegration::where('provider', 'discord')
                    ->where('platform_id', $data['discord_guild_id'])
                    ->first();

                if ($integration) {
                    $exists = $user->guildMemberships()
                        ->where('guild_id', $integration->guild_id)
                        ->exists();

                    if (!$exists) {
                        $user->guildMemberships()->create([
                            'guild_id' => $integration->guild_id,
                            'role' => \App\Enums\GuildRole::MEMBER,
                            'status' => 'active',
                            'joined_at' => now(),
                        ]);
                    }
                }
            }

            return $user->load(['profile', 'guildMemberships']);
        });
    }
}
