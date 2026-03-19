<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Models\LinkedAccount;
use App\Traits\ValidatesTelegramInitData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TelegramTmaRegisterAction
{
    use ValidatesTelegramInitData;

    public function execute(string $initData): array
    {
        if (!$this->validateInitData($initData)) {
            Log::warning('Telegram initData validation failed during register');
            throw ValidationException::withMessages(['initData' => 'Invalid Telegram data']);
        }

        $data = $this->parseInitData($initData);
        $telegramUser = json_decode($data['user'] ?? '{}', true);
        $telegramId = $telegramUser['id'] ?? null;

        if (!$telegramId) {
            throw ValidationException::withMessages(['initData' => 'Telegram ID not found']);
        }

        return DB::transaction(function () use ($telegramId, $telegramUser) {
            $linkedAccount = LinkedAccount::where('provider', 'telegram')
                ->where('provider_id', $telegramId)
                ->first();

            if ($linkedAccount) {
                $user = $linkedAccount->user;
            } else {
                $name = trim(($telegramUser['first_name'] ?? '') . ' ' . ($telegramUser['last_name'] ?? ''));
                if (empty($name)) {
                    $name = $telegramUser['username'] ?? 'TelegramPlayer';
                }

                $user = User::create([
                    'name' => $name,
                    'email' => null,
                    'password' => null,
                ]);

                $user->profile()->create([
                    'global_name' => $name,
                    'family_name' => '',
                    'char_class' => 'None',
                ]);

                LinkedAccount::create([
                    'user_id' => $user->id,
                    'provider' => 'telegram',
                    'provider_id' => $telegramId,
                    'username' => $telegramUser['username'] ?? null,
                    'display_name' => $name,
                    'avatar' => $telegramUser['photo_url'] ?? null,
                ]);
            }

            $user->tokens()->where('name', 'telegram-tma')->delete();
            $token = $user->createToken('telegram-tma')->plainTextToken;

            return [
                'token' => $token,
                'user' => $user->load(['profile', 'linked_accounts']),
            ];
        });
    }
}
