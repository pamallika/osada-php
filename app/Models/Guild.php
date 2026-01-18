<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guild extends Model
{
    protected $fillable = [
        'discord_id',
        'name',
        'admin_channel_id',
        'public_channel_id',
        'officer_role_ids'
    ];

    protected $casts = [
        'officer_role_ids' => 'array',
    ];

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function presets(): HasMany
    {
        return $this->hasMany(Preset::class);
    }
}
