<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GuildPostResource extends JsonResource
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
            'author' => new UserResource($this->whenLoaded('author')),
            'title' => $this->title,
            'position' => $this->position,
            'content' => $this->when($request->route()?->getActionMethod() !== 'index', $this->content),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
