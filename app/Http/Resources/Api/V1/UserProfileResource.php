<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserProfileResource extends JsonResource
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
            'user_id' => $this->user_id,
            'global_name' => $this->global_name,
            'family_name' => $this->family_name,
            'char_class' => $this->char_class,
            'gear_score' => $this->gear_score,
            'attack' => $this->attack,
            'awakening_attack' => $this->awakening_attack,
            'defense' => $this->defense,
            'draft_attack' => $this->draft_attack,
            'draft_awakening_attack' => $this->draft_awakening_attack,
            'draft_defense' => $this->draft_defense,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
