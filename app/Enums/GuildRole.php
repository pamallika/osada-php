<?php

namespace App\Enums;

class GuildRole
{
    public const LEADER = 'leader';
    public const OFFICER = 'officer';
    public const MEMBER = 'member';
    public const CREATOR = 'creator';
    public const ADMIN = 'admin';

    public static function all(): array
    {
        return [
            self::LEADER,
            self::OFFICER,
            self::MEMBER,
            self::CREATOR,
            self::ADMIN,
        ];
    }
}
