<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class CheckTelegramDeepLinkStatusAction
{
    /**
     * @param string $code
     * @return array{status: string, token?: string, user?: User}|null
     */
    public function execute(string $code): ?array
    {
        $status = Cache::get('telegram_auth_code_' . $code);

        if (!$status) {
            return null; // Expired or not found
        }

        if ($status === 'pending') {
            return ['status' => 'pending'];
        }

        // status is user_id
        $user = User::with(['profile', 'linked_accounts'])->find($status);
        
        if (!$user) {
            return null;
        }

        $user->tokens()->where('name', 'telegram-deeplink')->delete();
        $token = $user->createToken('telegram-deeplink')->plainTextToken;

        // Cleanup cache
        Cache::forget('telegram_auth_code_' . $code);

        return [
            'status' => 'success',
            'token' => $token,
            'user' => $user
        ];
    }
}
