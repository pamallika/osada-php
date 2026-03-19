<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Models\LinkedAccount;
use App\Traits\ValidatesTelegramInitData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TelegramTmaLinkAction
{
    use ValidatesTelegramInitData;

    public function execute(User $user, string $initData): User
    {
        if (!$this->validateInitData($initData)) {
            Log::warning('Telegram initData validation failed during link');
            throw ValidationException::withMessages(['initData' => 'Invalid Telegram data']);
        }

        $data = $this->parseInitData($initData);
        $telegramUser = json_decode($data['user'] ?? '{}', true);
        $telegramId = $telegramUser['id'] ?? null;

        if (!$telegramId) {
            throw ValidationException::withMessages(['initData' => 'Telegram ID not found']);
        }

        $existingLink = LinkedAccount::where('provider', 'telegram')
            ->where('provider_id', $telegramId)
            ->first();

        if ($existingLink && $existingLink->user_id !== $user->id) {
            throw ValidationException::withMessages(['initData' => 'Telegram account is already linked to another user']);
        }

        $name = trim(($telegramUser['first_name'] ?? '') . ' ' . ($telegramUser['last_name'] ?? ''));

        if ($existingLink) {
            $existingLink->update([
                'username' => $telegramUser['username'] ?? $existingLink->username,
                'display_name' => $name ?: $existingLink->display_name,
                'avatar' => $telegramUser['photo_url'] ?? $existingLink->avatar,
            ]);
        } else {
            LinkedAccount::create([
                'user_id' => $user->id,
                'provider' => 'telegram',
                'provider_id' => $telegramId,
                'username' => $telegramUser['username'] ?? null,
                'display_name' => $name ?: ($telegramUser['username'] ?? 'TelegramUser'),
                'avatar' => $telegramUser['photo_url'] ?? null,
            ]);
        }

        return $user->load(['profile', 'linked_accounts']);
    }
}
