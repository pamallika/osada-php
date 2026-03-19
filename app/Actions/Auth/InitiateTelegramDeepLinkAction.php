<?php

namespace App\Actions\Auth;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class InitiateTelegramDeepLinkAction
{
    /**
     * @return string Auth code
     */
    public function execute(): string
    {
        $code = Str::random(32);
        
        // Store empty user_id in cache, indicating pending status
        Cache::put('telegram_auth_code_' . $code, 'pending', now()->addMinutes(5));

        return $code;
    }
}
