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
        static::saved(function ($participant) {
            UpdateMessengerEventMessage::dispatch($participant->event_id)->delay(now()->addSeconds(5));
        });

        static::deleted(function ($participant) {
            UpdateMessengerEventMessage::dispatch($participant->event_id)->delay(now()->addSeconds(5));
        });
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
