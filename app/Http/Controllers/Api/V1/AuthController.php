<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\SocialProvider;
use App\Http\Controllers\Controller;
use App\Actions\Auth\LoginViaProviderAction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function login(
        string $provider,
        Request $request,
        LoginViaProviderAction $loginAction
    ): JsonResponse {
        if (!SocialProvider::tryFrom($provider)) {
            return response()->json(['message' => 'Unsupported provider'], 400);
        }

        // TODO: Здесь должна быть валидация токена от провайдера (Socialite / Telegram Hash)
        // Для примера симулируем, что мы успешно проверили токен и получили данные профиля:
        $providerData = $request->validate([
            'id' => 'required|string',
            'username' => 'nullable|string',
            'display_name' => 'nullable|string',
            'avatar' => 'nullable|string',
        ]);

        $token = $loginAction->execute($provider, $providerData);

        return response()->json([
            'message' => 'Successfully authenticated',
            'token' => $token,
            'token_type' => 'Bearer'
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->profile) {
            $user->profile()->create([
                'family_name' => '',
                'char_class' => 'None',
                'gear_score' => 0,
                'attack' => 0,
                'awakening_attack' => 0,
                'defense' => 0,
                'level' => 1,
            ]);
        }

        $user->load(['profile', 'linked_accounts', 'guildMemberships.guild']);

        return response()->json(['data' => $user]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'family_name' => 'required|string|max:255',
            'char_class' => 'required|string|max:255',
            'level' => 'required|integer|min:1|max:100',
            'attack' => 'required|integer|min:0|max:1000',
            'awk_attack' => 'required|integer|min:0|max:1000',
            'defense' => 'required|integer|min:0|max:1000',
        ]);

        $user = $request->user();

        $gearScore = max($validated['attack'], $validated['awk_attack']) + $validated['defense'];

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'family_name' => $validated['family_name'],
                'char_class' => $validated['char_class'],
                'level' => $validated['level'],
                'attack' => $validated['attack'],
                'awakening_attack' => $validated['awk_attack'],
                'defense' => $validated['defense'],
                'gear_score' => $gearScore,
            ]
        );

        return $this->me($request);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function redirect()
    {
        return Socialite::driver('discord')->stateless()->redirect();
    }

    public function callback(LoginViaProviderAction $loginAction)
    {
        try {
            $discordUser = Socialite::driver(SocialProvider::DISCORD->value)->stateless()->user();

            $providerData = [
                'id' => $discordUser->getId(),
                'username' => $discordUser->getNickname(), // In discord driver: nickname is the username
                'display_name' => $discordUser->user['global_name'] ?? $discordUser->getName(),
                'avatar' => $discordUser->getAvatar(),
            ];

            $token = $loginAction->execute(SocialProvider::DISCORD->value, $providerData);

            return redirect(config('app.frontend_url') . '/auth/callback?token=' . $token);

        } catch (\Exception $e) {
            return redirect(config('app.frontend_url') . '/login?error=auth_failed');
        }
    }
}
