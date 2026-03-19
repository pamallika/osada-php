<?php

namespace App\Actions\Auth;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class InitiateSocialLinkAction
{
    /**
     * @param int $userId
     * @return string
     */
    public function execute(int $userId): string
    {
        $linkCode = Str::random(32);
        Cache::put("social_link_{$linkCode}", $userId, now()->addMinutes(10));

        return $linkCode;
    }
}
