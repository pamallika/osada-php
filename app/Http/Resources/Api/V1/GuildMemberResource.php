<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GuildMemberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'guild' => new GuildResource($this->whenLoaded('guild')),
            'role' => $this->role,
            'status' => $this->status,
            'joined_at' => $this->joined_at,
        ];
    }
}
