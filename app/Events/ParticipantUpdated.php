<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ParticipantUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $eventId,
        public string $action,
        public array $participant
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('event.' . $this->eventId),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'event_id' => $this->eventId,
            'action' => $this->action,
            'participant' => $this->participant,
        ];
    }
}
