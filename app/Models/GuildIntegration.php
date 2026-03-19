<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuildIntegration extends Model
{
    protected $fillable = ['guild_id', 'provider', 'platform_id', 'platform_title', 'announcement_channel_id', 'settings'];

    protected $casts = [
        'settings' => 'array',
    ];

    public function guild(): BelongsTo
    {
        return $this->belongsTo(Guild::class);
    }
}
