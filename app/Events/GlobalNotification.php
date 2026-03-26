<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GlobalNotification implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $guildId,
        public string $type,
        public string $message,
        public ?string $linkUrl = null
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('guild.' . $this->guildId),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'type' => $this->type,
            'message' => $this->message,
            'link_url' => $this->linkUrl,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
