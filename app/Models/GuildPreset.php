<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuildPreset extends Model
{
    protected $fillable = ['guild_id', 'name', 'structure'];

    protected $casts = [
        'structure' => 'array',
    ];
}
