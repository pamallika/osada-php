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
            'id' => $this->id,
            'guild' => new GuildResource($this->whenLoaded('guild')),
            'user' => new UserResource($this->whenLoaded('user')),
            'role' => $this->role,
            'status' => $this->status,
            'verification_status' => $this->verification_status,
            'verified_by' => $this->when($this->verified_by, function() {
                return [
                    'id' => $this->verifier->id,
                    'name' => $this->verifier->name,
                    'profile' => [
                        'family_name' => $this->verifier->profile->family_name ?? null
                    ]
                ];
            }),
            'verified_at' => $this->verified_at,
            'joined_at' => $this->joined_at ?? $this->created_at, 
        ];
    }
}
