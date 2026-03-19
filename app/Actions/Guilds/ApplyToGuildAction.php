<?php

namespace App\Actions\Guilds;

use App\Models\Guild;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ApplyToGuildAction
{
    /**
     * @param User $user
     * @param string $slug
     * @throws ValidationException
     */
    public function execute(User $user, string $slug): void
    {
        $guild = Guild::where('invite_slug', $slug)
            ->where('status', 'active')
            ->first();

        if (!$guild) {
            throw ValidationException::withMessages([
                'slug' => ['Guild not found or inactive.'],
            ]);
        }

        // Check if already a member or has a pending application in ANY guild
        // Rules usually: one active guild at a time.
        $existingMembership = $user->guildMemberships()
            ->whereIn('status', ['active', 'pending'])
            ->first();

        if ($existingMembership) {
            if ($existingMembership->guild_id === $guild->id) {
                throw ValidationException::withMessages([
                    'guild' => ['You already have an active membership or pending application in this guild.'],
                ]);
            }
            
            throw ValidationException::withMessages([
                'guild' => ['You must leave your current guild or cancel your pending application before applying to a new one.'],
            ]);
        }

        $guild->members()->create([
            'user_id' => $user->id,
            'role' => 'member',
            'status' => 'pending',
        ]);
    }
}
