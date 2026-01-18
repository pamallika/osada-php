<?php

namespace App\Http\Resources\Api\Discord;

use Illuminate\Http\Resources\Json\JsonResource;

class EventFullResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name, // ДОБАВЛЕНО
            'description' => $this->description, // ДОБАВЛЕНО
            'region' => $this->region,
            'start_at' => $this->start_at->toIso8601String(),
            'total_slots' => $this->total_slots,
            'is_free_registration' => (bool)$this->is_free_registration,
            'status' => $this->status,
            'discord_message_id' => $this->discord_message_id,
            'public_channel_id' => $this->guild->public_channel_id ?? null,
            'squads' => SquadResource::collection($this->squads),
            'reserve' => ParticipantResource::collection($this->participants->whereNull('squad_id')),
            'stats' => [
                'total_confirmed' => $this->participants->where('status', 'confirmed')->count(),
                'total_filled' => $this->participants->count(),
            ]
        ];
    }
}
