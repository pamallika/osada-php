<?php

namespace App\Actions\Auth;

use App\Models\User;

class GetAuthenticatedUserAction
{
    public function execute(User $user): User
    {
        if (!$user->relationLoaded('profile') || !$user->profile) {
            $user->profile()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'global_name' => '',
                    'family_name' => '',
                    'char_class' => 'None',
                    'gear_score' => 0,
                    'attack' => 0,
                    'awakening_attack' => 0,
                    'defense' => 0,
                ]
            );
        }

        return $user->load(['profile', 'linked_accounts', 'guildMemberships.guild']);
    }
}
