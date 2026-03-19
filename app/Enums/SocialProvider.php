<?php

namespace App\Enums;

class SocialProvider
{
    public const DISCORD = 'discord';
    public const TELEGRAM = 'telegram';

    public static function values(): array
    {
        return [
            self::DISCORD,
            self::TELEGRAM,
        ];
    }
}
