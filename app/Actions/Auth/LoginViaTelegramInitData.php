<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Models\LinkedAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LoginViaTelegramInitData
{
    /**
     * @param string $initData
     * @return string|null Token if successful, null if validation fails
     */
    public function execute(string $initData): ?string
    {
        if (!$this->validate($initData)) {
            Log::warning('Telegram initData validation failed');
            return null;
        }

        $data = $this->parseInitData($initData);
        $telegramUser = json_decode($data['user'] ?? '{}', true);
        $telegramId = $telegramUser['id'] ?? null;

        if (!$telegramId) {
            return null;
        }

        return DB::transaction(function () use ($telegramId, $telegramUser) {
            $linkedAccount = LinkedAccount::where('provider', 'telegram')
                ->where('provider_id', $telegramId)
                ->first();

            if (!$linkedAccount) {
                // Если пользователь не найден через Telegram ID, мы пока не создаем его автоматически?
                // В ТЗ сказано: "Находить пользователя через LinkedAccount. Если пользователь найден – возвращать Sanctum токен."
                // Это предполагает, что пользователь УЖЕ должен быть привязан через /start или Discord.
                return null;
            }

            $user = $linkedAccount->user;

            // Update display name / avatar if needed
            $linkedAccount->update([
                'username' => $telegramUser['username'] ?? $linkedAccount->username,
                'display_name' => trim(($telegramUser['first_name'] ?? '') . ' ' . ($telegramUser['last_name'] ?? '')),
                'avatar' => $telegramUser['photo_url'] ?? $linkedAccount->avatar,
            ]);

            $user->tokens()->where('name', 'telegram-tma')->delete();
            return $user->createToken('telegram-tma')->plainTextToken;
        });
    }

    protected function validate(string $initData): bool
    {
        $botToken = env('TELEGRAM_BOT_TOKEN');
        if (!$botToken) {
            Log::error('TELEGRAM_BOT_TOKEN not set');
            return false;
        }

        parse_str($initData, $data);
        if (!isset($data['hash'])) {
            return false;
        }

        $hash = $data['hash'];
        unset($data['hash']);

        $dataCheckArr = [];
        foreach ($data as $key => $value) {
            $dataCheckArr[] = $key . '=' . $value;
        }
        sort($dataCheckArr);
        $dataCheckString = implode("\n", $dataCheckArr);

        $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
        $hashCheck = hash_hmac('sha256', $dataCheckString, $secretKey);

        return hash_equals($hashCheck, $hash);
    }

    protected function parseInitData(string $initData): array
    {
        parse_str($initData, $data);
        return $data;
    }
}
