<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class CheckTelegramDeepLinkStatusAction
{
    /**
     * @param string $code
     * @param string|null $verifier
     * @return array{status: string, token?: string, user?: User}|null
     */
    public function execute(string $code, ?string $verifier = null): ?array
    {
        $data = Cache::get('telegram_auth_code_' . $code);

        if (!$data || !is_array($data)) {
            return null; // Expired or not found
        }

        if ($data['status'] === 'pending') {
            return ['status' => 'pending'];
        }

        // status is user_id. If verifier is provided, check if it matches verifier_hash (PKCE)
        if ($verifier && isset($data['verifier_hash'])) {
            $expectedHash = hash('sha256', $verifier);
            if ($expectedHash !== $data['verifier_hash']) {
                return null;
            }
        } elseif (!$verifier) {
            // Verifier is required for final token issue
            return ['status' => 'pending'];
        }

        $user = User::with(['profile', 'linked_accounts'])->find($data['status']);
        
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
