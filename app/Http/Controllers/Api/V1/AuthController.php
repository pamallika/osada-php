<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\SocialProvider;
use App\Http\Controllers\Controller;
use App\Actions\Auth\LoginViaProviderAction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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
        $user = $request->user()->load('linkedAccounts', 'guildMemberships.guild');

        return response()->json(['data' => $user]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
