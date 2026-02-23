<?php

namespace App\Enums;

enum GuildRole: string
{
    case LEADER = 'leader';
    case OFFICER = 'officer';
    case MEMBER = 'member';
}
