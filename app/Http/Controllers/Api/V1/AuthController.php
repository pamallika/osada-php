<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\GetAuthenticatedUserAction;
use App\Actions\Auth\LoginUserAction;
use App\Actions\Auth\LoginViaProviderAction;
use App\Actions\Auth\RegisterUserAction;
use App\Actions\Auth\UpdateUserProfileAction;
use App\Enums\SocialProvider;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Requests\Api\V1\Auth\UpdateProfileRequest;
use App\Traits\ApiResponser;
use App\Actions\Telegram\GenerateTelegramBindingLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

use App\Http\Requests\Api\V1\Auth\UpdateAccountRequest;
use App\Actions\Auth\UpdateUserAccountAction;
use App\Actions\Auth\UnlinkAccountAction;
use App\Actions\Auth\InitiateTelegramDeepLinkAction;
use App\Actions\Auth\CheckTelegramDeepLinkStatusAction;
use App\Actions\Auth\InitiateSocialLinkAction;
use App\Actions\Auth\TelegramTmaVerifyAction;
use App\Actions\Auth\TelegramTmaRegisterAction;
use App\Actions\Auth\TelegramTmaLinkAction;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class AuthController extends Controller
{
    use ApiResponser;

    public function initSocialLink(Request $request, InitiateSocialLinkAction $action): JsonResponse
    {
        $code = $action->execute($request->user()->id);
        return $this->successResponse(['link_code' => $code]);
    }

    public function telegramLink(Request $request, GenerateTelegramBindingLink $action): JsonResponse
    {
        $link = $action->execute($request->user()->id);
        return $this->successResponse(['link' => $link]);
    }

    public function register(RegisterRequest $request, RegisterUserAction $action): JsonResponse
    {
        $result = $action->execute($request->validated());

        $user = $result['user']->load(['profile', 'linked_accounts', 'guildMemberships.guild']);
        return $this->successResponse([
            'token' => $result['token'],
            'user' => new UserResource($user),
        ], 'User registered successfully', 201);
    }

    public function basicLogin(LoginRequest $request, LoginUserAction $action): JsonResponse
    {
        $result = $action->execute($request->validated());

        $user = $result['user']->load(['profile', 'linked_accounts', 'guildMemberships.guild']);
        return $this->successResponse([
            'token' => $result['token'],
            'user' => new UserResource($user),
        ], 'Logged in successfully');
    }

    public function login(
        string $provider,
        Request $request,
        LoginViaProviderAction $loginAction
    ): JsonResponse {
        if (!in_array($provider, SocialProvider::values())) {
            return $this->errorResponse('Unsupported provider', 400);
        }

        $providerData = $request->validate([
            'id' => 'required|string',
            'username' => 'nullable|string',
            'display_name' => 'nullable|string',
            'avatar' => 'nullable|string',
        ]);

        $token = $loginAction->execute($provider, $providerData);

        return $this->successResponse([
            'token' => $token,
            'token_type' => 'Bearer'
        ], 'Successfully authenticated');
    }

    public function initTelegramDeepLink(Request $request, InitiateTelegramDeepLinkAction $action): JsonResponse
    {
        $request->validate([
            'verifier_hash' => 'required|string'
        ]);

        $code = $action->execute($request->input('verifier_hash'));
        return $this->successResponse(['auth_code' => $code]);
    }

    public function checkTelegramDeepLink(Request $request, string $code, CheckTelegramDeepLinkStatusAction $action): JsonResponse
    {
        $verifier = $request->query('verifier');
        $result = $action->execute($code, $verifier);

        if (!$result) {
            return $this->errorResponse('Invalid or expired auth code.', 404);
        }

        if ($result['status'] === 'pending') {
            return response()->json([
                'status' => 'success',
                'data' => ['status' => 'pending']
            ], 200);
        }

        $user = $result['user']->load(['profile', 'linked_accounts', 'guildMemberships.guild']);
        return $this->successResponse([
            'token' => $result['token'],
            'user' => new UserResource($user),
        ], 'Successfully authenticated via Telegram Deep Link');
    }

    public function loginViaTelegram(
        Request $request,
        \App\Actions\Auth\AuthenticateTelegramAction $widgetAction
    ): JsonResponse {
        $result = $widgetAction->execute($request->all());

        $user = $result['user']->load(['profile', 'linked_accounts', 'guildMemberships.guild']);

        return $this->successResponse([
            'token' => $result['token'],
            'user' => new UserResource($user),
        ], 'Successfully authenticated via Telegram Widget');
    }

    public function tmaVerify(Request $request, TelegramTmaVerifyAction $action): JsonResponse
    {
        $request->validate(['initData' => 'required|string']);

        $result = $action->execute($request->input('initData'));

        if (!$result) {
            return $this->errorResponse('Not Found', 404);
        }

        $user = $result['user']->load(['profile', 'linked_accounts', 'guildMemberships.guild']);
        return $this->successResponse([
            'token' => $result['token'],
            'user' => new UserResource($user),
        ], 'Successfully verified via TMA');
    }

    public function tmaRegister(Request $request, TelegramTmaRegisterAction $action): JsonResponse
    {
        $request->validate(['initData' => 'required|string']);

        $result = $action->execute($request->input('initData'));

        $user = $result['user']->load(['profile', 'linked_accounts', 'guildMemberships.guild']);
        return $this->successResponse([
            'token' => $result['token'],
            'user' => new UserResource($user),
        ], 'Successfully registered via TMA', 201);
    }

    public function tmaLink(Request $request, TelegramTmaLinkAction $action): JsonResponse
    {
        $request->validate(['initData' => 'required|string']);

        $user = $action->execute($request->user(), $request->input('initData'));

        return $this->successResponse(new UserResource($user), 'Successfully linked Telegram account');
    }

    public function me(Request $request, GetAuthenticatedUserAction $action): JsonResponse
    {
        $user = $action->execute($request->user());

        return $this->successResponse(new UserResource($user));
    }

    public function updateProfile(UpdateProfileRequest $request, UpdateUserProfileAction $action): JsonResponse
    {
        $user = $action->execute($request->user(), $request->validated());
        $user->load(['profile', 'linked_accounts', 'guildMemberships.guild']);

        return $this->successResponse(new UserResource($user), 'Profile updated successfully');
    }

    public function updateAccount(UpdateAccountRequest $request, UpdateUserAccountAction $action): JsonResponse
    {
        $user = $action->execute($request->user(), $request->validated());
        $user->load(['profile', 'linked_accounts', 'guildMemberships.guild']);

        return $this->successResponse(new UserResource($user), 'Account updated successfully');
    }

    public function unlinkAccount(string $provider, Request $request, UnlinkAccountAction $action): JsonResponse
    {
        $user = $action->execute($request->user(), $provider);
        $user->load(['profile', 'linked_accounts', 'guildMemberships.guild']);

        return $this->successResponse(new UserResource($user), 'Account unlinked successfully');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Logged out successfully');
    }

    public function redirect(Request $request)
    {
        $linkCode = $request->query('link_code');

        $driver = Socialite::driver(SocialProvider::DISCORD);

        if ($linkCode) {
            $driver->with(['state' => $linkCode]);
        }

        return $driver->stateless()->redirect();
    }

    public function callback(Request $request, LoginViaProviderAction $loginAction)
    {
        try {
            $discordUser = Socialite::driver(SocialProvider::DISCORD)->stateless()->user();
            
            $state = $request->input('state');
            $currentUser = null;

            if ($state) {
                $userId = Cache::get("social_link_{$state}");
                if ($userId) {
                    $currentUser = User::find($userId);
                    Cache::forget("social_link_{$state}");
                }
            }

            $providerData = [
                'id' => $discordUser->getId(),
                'username' => $discordUser->getNickname(),
                'display_name' => $discordUser->user['global_name'] ?? $discordUser->getName(),
                'avatar' => $discordUser->getAvatar(),
            ];

            $token = $loginAction->execute(SocialProvider::DISCORD, $providerData, $currentUser);

            if ($currentUser) {
                return redirect(config('app.frontend_url') . '/profile?linked=success');
            }

            return redirect(config('app.frontend_url') . '/auth/callback?token=' . $token);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect(config('app.frontend_url') . '/profile?error=already_linked');
        } catch (\Exception $e) {
            Log::error('Discord Callback Error: ' . $e->getMessage());
            return redirect(config('app.frontend_url') . '/login?error=auth_failed');
        }
    }
}
