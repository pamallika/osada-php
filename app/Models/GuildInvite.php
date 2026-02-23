<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuildInvite extends Model
{
    protected $fillable = [
        'guild_id',
        'token',
        'max_uses',
        'uses',
        'expires_at',
        'created_by'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function guild(): BelongsTo
    {
        return $this->belongsTo(Guild::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isValid(): bool
    {
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_uses !== null && $this->uses >= $this->max_uses) {
            return false;
        }

        return true;
    }
}
