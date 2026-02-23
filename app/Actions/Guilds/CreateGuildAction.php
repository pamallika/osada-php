<?php

namespace App\Actions\Guilds;

use App\Models\Guild;
use App\Models\User;
use App\Enums\GuildRole;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CreateGuildAction
{
    public function execute(User $user, array $data): Guild
    {
        return DB::transaction(function () use ($user, $data) {
            $guild = Guild::query()->create([
                'name' => $data['name'],
                'slug' => Str::slug($data['name']) . '-' . Str::random(4),
                'owner_id' => $user->id,
                'logo_url' => $data['logo_url'] ?? null,
            ]);

            $guild->members()->create([
                'user_id' => $user->id,
                'role' => GuildRole::LEADER,
            ]);

            return $guild;
        });
    }
}
