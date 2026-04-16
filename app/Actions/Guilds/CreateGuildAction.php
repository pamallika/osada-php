<?php

namespace App\Actions\Guilds;

use App\Models\Guild;
use App\Models\User;
use App\Enums\GuildRole;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateGuildAction
{
    /**
     * @param User $user
     * @param array{name: string, logo_url?: string} $data
     * @return Guild
     * @throws ValidationException
     */
    public function execute(User $user, array $data): Guild
    {
        // Check if user is already in a guild
        if ($user->guildMemberships()->exists()) {
            throw ValidationException::withMessages([
                'name' => ['You are already a member of a guild.'],
            ]);
        }

        return DB::transaction(function () use ($user, $data) {
            $guild = Guild::create([
                'name' => $data['name'],
                'slug' => Str::slug($data['name']) . '-' . Str::random(4),
                'owner_id' => $user->id,
                'logo_url' => $data['logo_url'] ?? null,
            ]);

            $guild->members()->create([
                'user_id' => $user->id,
                'role' => GuildRole::CREATOR,
                'status' => 'active',
                'joined_at' => now(),
            ]);

            $guild->membershipHistories()->create([
                'user_id' => $user->id,
                'action' => 'join',
            ]);

            return $guild;
        });
    }
}
