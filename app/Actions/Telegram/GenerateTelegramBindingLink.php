<?php

namespace App\Actions\Telegram;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class GenerateTelegramBindingLink
{
    /**
     * Generate a deep link for personal account binding or guild binding.
     * 
     * @param int|null $userId User ID for personal binding
     * @param int|null $guildId Guild ID for group binding
     * @return string
     */
    public function execute(?int $userId = null, ?int $guildId = null): string
    {
        $token = Str::random(32);
        $botUsername = config('telegram.bots.' . config('telegram.default') . '.username') 
            ?? config('telegram.bot_name', env('TELEGRAM_BOT_NAME', 'SageSiegeBot'));

        if ($userId) {
            Cache::put('telegram_token_' . $token, $userId, now()->addMinutes(15));
            return "https://t.me/{$botUsername}?start={$token}";
        }

        if ($guildId) {
            Cache::put('guild_telegram_bind_token_' . $token, $guildId, now()->addMinutes(15));
            // For guild binding, we might need a different instruction, 
            // but the deep link is the same, user just needs to paste it in the group.
            // Or better: user sends /bind {token} in the group.
            return $token;
        }

        return '';
    }
}
