<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait ValidatesTelegramInitData
{
    protected function validateInitData(string $initData): bool
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
