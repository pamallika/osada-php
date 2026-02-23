<?php

namespace App\Models;

use App\Enums\GuildRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuildMember extends Model
{
    protected $fillable = ['guild_id', 'user_id', 'role'];

    protected $casts = [
        'role' => GuildRole::class,
    ];

    public function guild(): BelongsTo
    {
        return $this->belongsTo(Guild::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
