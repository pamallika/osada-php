<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class UnlinkAccountAction
{
    /**
     * @param User $user
     * @param string $provider
     * @return User
     * @throws ValidationException
     */
    public function execute(User $user, string $provider): User
    {
        $linkedAccount = $user->linkedAccounts()->where('provider', $provider)->first();

        if (!$linkedAccount) {
            throw ValidationException::withMessages([
                'provider' => ['This account is not linked.'],
            ]);
        }

        // КРИТИЧЕСКАЯ ВАЛИДАЦИЯ:
        // Запретить отвязку, если это единственный способ входа (нет других привязок)
        // и при этом у пользователя не задан password.

        $otherLinkedAccountsCount = $user->linkedAccounts()->where('provider', '!=', $provider)->count();
        $hasPassword = !empty($user->password);

        if ($otherLinkedAccountsCount === 0 && !$hasPassword) {
            throw ValidationException::withMessages([
                'provider' => ['You cannot unlink the last authentication method. Set a password first or link another account.'],
            ]);
        }

        $linkedAccount->delete();

        return $user;
    }
}
