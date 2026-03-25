<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EventUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $eventId,
        public string $action,
        public array $eventPatch
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
            'event_patch' => $this->eventPatch,
        ];
    }
}
