<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Models\LinkedAccount;
use Illuminate\Support\Facades\DB;

class LoginViaProviderAction
{
    public function execute(string $provider, array $providerData): string
    {
        return DB::transaction(function () use ($provider, $providerData) {
            $linkedAccount = LinkedAccount::where('provider', $provider)
                ->where('provider_id', $providerData['id'])
                ->first();

            if ($linkedAccount) {
                $linkedAccount->update([
                    'username' => $providerData['username'] ?? $linkedAccount->username,
                    'avatar' => $providerData['avatar'] ?? $linkedAccount->avatar,
                ]);
                $user = $linkedAccount->user;
            } else {
                $user = User::query()->create([
                    'name' => $providerData['username'] ?? 'Unknown User',
                ]);

                $user->linkedAccounts()->create([
                    'provider' => $provider,
                    'provider_id' => $providerData['id'],
                    'username' => $providerData['username'] ?? null,
                    'avatar' => $providerData['avatar'] ?? null,
                ]);
            }
            $user->tokens()->where('name', 'web-client')->delete();

            return $user->createToken('web-client')->plainTextToken;
        });
    }
}
