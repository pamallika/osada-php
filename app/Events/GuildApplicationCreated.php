<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GuildApplicationCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $guildId,
        public int $pendingCount
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
            'guild_id' => $this->guildId,
            'pending_count' => $this->pendingCount,
            'type' => 'new_application'
        ];
    }
}
