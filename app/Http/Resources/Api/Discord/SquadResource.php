<?php

namespace App\Http\Resources\Api\Discord;

use Illuminate\Http\Resources\Json\JsonResource;

class SquadResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->title,
            'limit' => $this->slots_limit,
            'current_count' => $this->participants->count(),
            'participants' => ParticipantResource::collection($this->participants),
        ];
    }
}
