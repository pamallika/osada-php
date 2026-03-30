<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Models\UserProfile;

class UpdateUserProfileAction
{
    /**
     * @param User $user
     * @param array{global_name?: string, family_name: string, char_class?: string, attack?: int, awakening_attack?: int, defense?: int} $data
     * @return User
     */
    public function execute(User $user, array $data): User
    {
        $attack = $data['attack'] ?? 0;
        $awkAttack = $data['awakening_attack'] ?? 0;
        $defense = $data['defense'] ?? 0;
        $gearScore = max($attack, $awkAttack) + $defense;

        $updateData = [
            'family_name' => $data['family_name'],
            'char_class' => $data['char_class'] ?? 'None',
            'attack' => $attack,
            'awakening_attack' => $awkAttack,
            'defense' => $defense,
            'gear_score' => $gearScore,
            'draft_attack' => $data['draft_attack'] ?? null,
            'draft_awakening_attack' => $data['draft_awakening_attack'] ?? null,
            'draft_defense' => $data['draft_defense'] ?? null,
        ];

        if (isset($data['global_name'])) {
            $updateData['global_name'] = $data['global_name'];
        }

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            $updateData
        );

        return $user->load('profile');
    }
}
