<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Models\LinkedAccount;
use Illuminate\Support\Facades\DB;

class LoginViaProviderAction
{
    public function execute(string $provider, array $providerData, ?User $currentUser = null): string
    {
        return DB::transaction(function () use ($provider, $providerData, $currentUser) {
            $linkedAccount = LinkedAccount::where('provider', $provider)
                ->where('provider_id', $providerData['id'])
                ->first();

            if ($currentUser) {
                // Scenario: Linking social account to existing user
                if ($linkedAccount && $linkedAccount->user_id !== $currentUser->id) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'provider' => ["This {$provider} account is already linked to another SAGE profile."],
                    ]);
                }

                if ($linkedAccount) {
                    $linkedAccount->update([
                        'username' => $providerData['username'] ?? $linkedAccount->username,
                        'display_name' => $providerData['display_name'] ?? $linkedAccount->display_name,
                        'avatar' => $providerData['avatar'] ?? $linkedAccount->avatar,
                    ]);
                } else {
                    $currentUser->linkedAccounts()->create([
                        'provider' => $provider,
                        'provider_id' => $providerData['id'],
                        'username' => $providerData['username'] ?? null,
                        'display_name' => $providerData['display_name'] ?? null,
                        'avatar' => $providerData['avatar'] ?? null,
                    ]);
                }
                $user = $currentUser;
            } else {
                // Scenario: Login or Registration
                if ($linkedAccount) {
                    $linkedAccount->update([
                        'username' => $providerData['username'] ?? $linkedAccount->username,
                        'display_name' => $providerData['display_name'] ?? $linkedAccount->display_name,
                        'avatar' => $providerData['avatar'] ?? $linkedAccount->avatar,
                    ]);
                    $user = $linkedAccount->user;
                } else {
                    $user = User::query()->create();

                    $user->linkedAccounts()->create([
                        'provider' => $provider,
                        'provider_id' => $providerData['id'],
                        'username' => $providerData['username'] ?? null,
                        'display_name' => $providerData['display_name'] ?? null,
                        'avatar' => $providerData['avatar'] ?? null,
                    ]);
                }
            }

            // Актуализация Global Name
            if (isset($providerData['display_name'])) {
                if (!$user->profile || empty($user->profile->global_name)) {
                    $user->profile()->updateOrCreate(
                        ['user_id' => $user->id],
                        [
                            'global_name' => $providerData['display_name'],
                            'family_name' => $user->profile?->family_name ?? '',
                            'char_class' => $user->profile?->char_class ?? 'None',
                        ]
                    );
                }
            }

            $user->tokens()->where('name', 'web-client')->delete();

            return $user->createToken('web-client')->plainTextToken;
        });
    }
}
