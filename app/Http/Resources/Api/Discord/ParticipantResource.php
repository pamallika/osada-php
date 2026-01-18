<?php

namespace App\Http\Resources\Api\Discord;

use Illuminate\Http\Resources\Json\JsonResource;

class ParticipantResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'display_name' => $this->user->profile->family_name
                ?? $this->user->global_name
                    ?? $this->user->username,
            'status' => $this->status,
        ];
    }
}
