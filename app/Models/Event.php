<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    protected $fillable = ['guild_id', 'region', 'start_at', 'total_slots', 'is_free_registration', 'status', 'discord_message_id'];

    protected $casts = [
        'start_at' => 'datetime',
        'is_free_registration' => 'boolean',
    ];

    public function squads(): HasMany
    {
        return $this->hasMany(EventSquad::class)->orderBy('position');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(EventParticipant::class);
    }

    public function guild(): BelongsTo
    {
        return $this->belongsTo(Guild::class);
    }
}
