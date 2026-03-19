<?php

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class GetPlayerProfileAction
{
    /**
     * @param int $userId
     * @return User
     * @throws ModelNotFoundException
     */
    public function execute(int $userId): User
    {
        return User::with(['profile', 'linked_accounts', 'guildMemberships.guild'])
            ->findOrFail($userId);
    }
}
