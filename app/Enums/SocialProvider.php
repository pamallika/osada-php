<?php

namespace App\Enums;

enum SocialProvider: string
{
    case DISCORD = 'discord';
    case TELEGRAM = 'telegram';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
