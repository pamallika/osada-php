<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserGearMediaResource extends JsonResource
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
            'url' => $this->url ? asset($this->url) : null,
            'label' => $this->label,
            'is_draft' => $this->is_draft,
            'size' => $this->size,
            'created_at' => $this->created_at,
        ];
    }
}
