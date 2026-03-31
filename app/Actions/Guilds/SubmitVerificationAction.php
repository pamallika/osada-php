<?php

namespace App\Actions\Guilds;

use App\Models\GuildMember;
use App\Models\User;
use App\Models\UserGearMedia;
use App\Traits\ApiResponser;

class SubmitVerificationAction
{
    /**
     * Submit user profile for gear verification.
     */
    public function execute(User $user): GuildMember
    {
        $membership = GuildMember::where('user_id', $user->id)
            ->where('status', 'active')
            ->firstOrFail();

        // 1. Mandatory 4-labels validation
        $mandatoryLabels = ['crystal', 'relic', 'zakalk', 'gear'];
        $existingLabels = UserGearMedia::where('user_id', $user->id)
            ->whereIn('label', $mandatoryLabels)
            ->pluck('label')
            ->unique()
            ->toArray();

        $missingLabels = array_diff($mandatoryLabels, $existingLabels);

        if (!empty($missingLabels)) {
             throw \Illuminate\Validation\ValidationException::withMessages([
                 'gear' => 'Uploaded screenshots are incomplete (' . implode(', ', $missingLabels) . ')'
             ]);
        }

        // 2. Set status
        $membership->update([
            'verification_status' => 'pending',
        ]);

        return $membership;
    }
}
