<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'guild_id' => $this->guild_id,
            'name' => $this->name,
            'description' => $this->description,
            'start_at' => $this->start_at->toIso8601String(),
            'status' => $this->status,
            'notification_settings' => $this->notification_settings,
            'discord_message_id' => $this->discord_message_id,
            'telegram_message_id' => $this->telegram_message_id,
            'total_slots' => $this->total_slots,
            'stats' => [
                'total_confirmed' => $this->participants()->where('status', 'confirmed')->count(),
                'total_declined' => $this->participants()->where('status', 'declined')->count(),
                'total_pending' => $this->participants()->where('status', 'pending')->count(),
            ],
            'squads' => SquadResource::collection($this->whenLoaded('squads')),
        ];
    }
}
