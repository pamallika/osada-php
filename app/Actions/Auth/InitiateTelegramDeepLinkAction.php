<?php

namespace App\Actions\Auth;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class InitiateTelegramDeepLinkAction
{
    /**
     * @param string $verifierHash
     * @return string Auth code
     */
    public function execute(string $verifierHash): string
    {
        $code = Str::random(32);
        
        // Store pending status and verifier hash in cache
        Cache::put('telegram_auth_code_' . $code, [
            'status' => 'pending',
            'verifier_hash' => $verifierHash
        ], now()->addMinutes(10));

        return $code;
    }
}
