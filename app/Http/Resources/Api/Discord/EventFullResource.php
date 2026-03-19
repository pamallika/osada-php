<?php

namespace App\Http\Resources\Api\Discord;

use Illuminate\Http\Resources\Json\JsonResource;

class EventFullResource extends JsonResource
{
    public function toArray($request)
    {
        $pendingParticipants = $this->participants->where('status', 'pending');
        $declinedParticipants = $this->participants->where('status', 'declined');
        $confirmedParticipants = $this->participants->where('status', 'confirmed');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'start_at' => $this->start_at->toIso8601String(),
            'total_slots' => $this->total_slots,
            'is_free_registration' => (bool)$this->is_free_registration,
            'status' => $this->status,
            'notification_settings' => $this->notification_settings,
            'discord_message_id' => $this->discord_message_id,
            'public_channel_id' => $this->guild->public_channel_id ?? null,
            'squads' => SquadResource::collection($this->squads),
            'pending_users' => $pendingParticipants->values()->map(fn($p) => [
                'id' => $p->user->id,
                'name' => !empty($p->user->profile?->family_name) 
                    ? $p->user->profile->family_name 
                    : (!empty($p->user->profile?->global_name) ? $p->user->profile->global_name : 'User_' . $p->user->id),
                'family_name' => $p->user->profile->family_name ?? 'Unknown',
                'global_name' => $p->user->profile->global_name ?? null,
                'profile' => $p->user->profile,
            ]),
            'declined_users' => $declinedParticipants->values()->map(fn($p) => [
                'id' => $p->user->id,
                'name' => !empty($p->user->profile?->family_name) 
                    ? $p->user->profile->family_name 
                    : (!empty($p->user->profile?->global_name) ? $p->user->profile->global_name : 'User_' . $p->user->id),
                'family_name' => $p->user->profile->family_name ?? 'Unknown',
                'global_name' => $p->user->profile->global_name ?? null,
                'profile' => $p->user->profile,
            ]),
            'stats' => [
                'total_confirmed' => $confirmedParticipants->count(),
                'total_declined' => $declinedParticipants->count(),
                'total_pending' => $pendingParticipants->count(),
                'total_slots' => $this->squads()->where('is_system', false)->sum('slots_limit'),
            ]
        ];
    }
}
