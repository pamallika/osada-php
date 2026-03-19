<?php

namespace App\Actions\Auth;

use App\Models\LinkedAccount;
use App\Traits\ValidatesTelegramInitData;
use Illuminate\Support\Facades\Log;

class TelegramTmaVerifyAction
{
    use ValidatesTelegramInitData;

    public function execute(string $initData): ?array
    {
        if (!$this->validateInitData($initData)) {
            Log::warning('Telegram initData validation failed during verify');
            return null;
        }

        $data = $this->parseInitData($initData);
        $telegramUser = json_decode($data['user'] ?? '{}', true);
        $telegramId = $telegramUser['id'] ?? null;

        if (!$telegramId) {
            return null;
        }

        $linkedAccount = LinkedAccount::where('provider', 'telegram')
            ->where('provider_id', $telegramId)
            ->first();

        if (!$linkedAccount) {
            return null;
        }

        $user = $linkedAccount->user;

        $linkedAccount->update([
            'username' => $telegramUser['username'] ?? $linkedAccount->username,
            'display_name' => trim(($telegramUser['first_name'] ?? '') . ' ' . ($telegramUser['last_name'] ?? '')),
            'avatar' => $telegramUser['photo_url'] ?? $linkedAccount->avatar,
        ]);

        $user->tokens()->where('name', 'telegram-tma')->delete();
        $token = $user->createToken('telegram-tma')->plainTextToken;

        return [
            'token' => $token,
            'user' => $user->load(['profile', 'linked_accounts']),
        ];
    }
}
