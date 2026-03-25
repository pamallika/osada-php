<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'email' => $this->email,
            'name' => $this->name,
            'has_password' => !empty($this->password),
            'profile' => new UserProfileResource($this->whenLoaded('profile')),
            'linked_accounts' => LinkedAccountResource::collection($this->whenLoaded('linked_accounts')),
            'guild_memberships' => GuildMemberResource::collection($this->whenLoaded('guildMemberships')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
