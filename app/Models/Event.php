<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;
    protected $fillable = [
        'name', 
        'description', 
        'guild_id', 
        'start_at', 
        'total_slots', 
        'is_free_registration', 
        'status', 
        'notification_settings',
        'discord_message_id',
        'telegram_message_id'
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'is_free_registration' => 'boolean',
        'notification_settings' => 'array',
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
