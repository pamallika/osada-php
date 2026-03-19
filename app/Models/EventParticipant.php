<?php

namespace App\Models;

use App\Jobs\UpdateMessengerEventMessage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventParticipant extends Model
{
    protected $fillable = ['event_id', 'user_id', 'squad_id', 'status'];

    protected static function booted()
    {
        // Removed automatic job dispatch on saved/deleted events
        // as this causes queue spam when creating multiple participants.
        // It must be called explicitly in controllers or actions after data is saved.
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function squad(): BelongsTo
    {
        return $this->belongsTo(EventSquad::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
