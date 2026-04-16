<?php

namespace App\Http\Resources\Api\Discord;

use Illuminate\Http\Resources\Json\JsonResource;

class ParticipantResource extends JsonResource
{
    public function toArray($request)
    {
        $profile = $this->user->profile;
        $discordAccount = $this->user->linkedAccounts->where('provider', 'discord')->first();

        return [
            'user_id' => $this->user_id,
            'discord_id' => $discordAccount->provider_id ?? null,
            'family_name' => $profile->family_name ?? 'Unknown',
            'global_name' => $profile->global_name ?? null,
            
            // display_name для обратной совместимости или удобства фронта
            'display_name' => !empty($profile->family_name) 
                ? $profile->family_name 
                : (!empty($profile->global_name) ? $profile->global_name : 'User_' . $this->user_id),

            'char_class' => $profile->char_class ?? null,
            'verification_status' => $profile->verification_status ?? null,
            'status' => $this->status,
        ];
    }
}
