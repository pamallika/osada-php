<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Models\LinkedAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthenticateTelegramAction
{
    /**
     * @param array $data
     * @return array{token: string, user: User}
     * @throws ValidationException
     */
    public function execute(array $data): array
    {
        if (!$this->validate($data)) {
            Log::warning('Telegram login hash validation failed', ['data' => $data]);
            throw ValidationException::withMessages([
                'hash' => ['Invalid Telegram authentication data.'],
            ]);
        }

        $telegramId = $data['id'];
        $username = $data['username'] ?? null;
        $firstName = $data['first_name'] ?? '';
        $lastName = $data['last_name'] ?? '';
        $photoUrl = $data['photo_url'] ?? null;
        $displayName = trim($firstName . ' ' . $lastName);

        $user = $this->findOrCreateUser($telegramId, $username, $displayName, $photoUrl);

        $user->tokens()->where('name', 'telegram-login')->delete();
        $token = $user->createToken('telegram-login')->plainTextToken;

        return [
            'token' => $token,
            'user' => $user->load(['profile', 'linked_accounts']),
        ];
    }

    public function findOrCreateUser($telegramId, $username, $displayName, $photoUrl): User
    {
        return DB::transaction(function () use ($telegramId, $username, $displayName, $photoUrl) {
            $linkedAccount = LinkedAccount::where('provider', 'telegram')
                ->where('provider_id', $telegramId)
                ->first();

            if ($linkedAccount) {
                $user = $linkedAccount->user;
                
                // Update linked account data
                $linkedAccount->update([
                    'username' => $username,
                    'display_name' => $displayName,
                    'avatar' => $photoUrl,
                ]);
            } else {
                // Create new user
                $user = User::create([
                    'password' => null, // No password for social login by default
                ]);

                // Create profile
                $user->profile()->create([
                    'global_name' => $displayName ?: $username,
                    'family_name' => '',
                    'char_class' => 'None',
                    'attack' => 0,
                    'awakening_attack' => 0,
                    'defense' => 0,
                ]);

                // Create linked account
                $user->linkedAccounts()->create([
                    'provider' => 'telegram',
                    'provider_id' => $telegramId,
                    'username' => $username,
                    'display_name' => $displayName,
                    'avatar' => $photoUrl,
                ]);
            }

            return $user;
        });
    }

    protected function validate(array $data): bool
    {
        $botToken = config('services.telegram.bot_token') ?: env('TELEGRAM_BOT_TOKEN');
        if (!$botToken) {
            Log::error('TELEGRAM_BOT_TOKEN not set');
            return false;
        }

        if (!isset($data['hash'])) {
            return false;
        }

        $hash = $data['hash'];
        $checkData = $data;
        unset($checkData['hash']);

        $dataCheckArr = [];
        foreach ($checkData as $key => $value) {
            if ($value !== null) {
                $dataCheckArr[] = $key . '=' . $value;
            }
        }
        sort($dataCheckArr);
        $dataCheckString = implode("\n", $dataCheckArr);

        $secretKey = hash('sha256', $botToken, true);
        $hashCheck = hash_hmac('sha256', $dataCheckString, $secretKey);

        return hash_equals($hashCheck, $hash);
    }
}
