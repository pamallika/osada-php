<?php

use App\Actions\GetSystemStatusAction;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\EventParticipantController;
use App\Http\Controllers\Api\V1\EventSquadController;
use App\Http\Controllers\Api\V1\DiscordGuildController;
use App\Http\Controllers\Api\V1\GuildController as V1GuildController;
use App\Http\Controllers\Api\V1\GuildPresetController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\GuildInviteController;
use App\Http\Controllers\Api\V1\GuildApplicationController;
use App\Http\Controllers\Api\V1\GuildMemberController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\TelegramWebhookController;
use App\Http\Controllers\Api\V1\GuildIntegrationController;
use App\Http\Controllers\Api\V1\GearController;
use App\Http\Controllers\Api\V1\GuildVerificationController;
use App\Http\Controllers\Api\V1\GuildPostController;
use App\Http\Controllers\Api\V1\PublicGuildController;

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\V1\GuildController;

Route::prefix('v1')->middleware('bot_proxy')->group(function () {

    Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle']);
    Route::get('/status', GetSystemStatusAction::class);

    Route::prefix('public/guilds')->group(function () {
        Route::get('/', [PublicGuildController::class, 'index']);
        Route::get('/{slug}', [PublicGuildController::class, 'show']);
        Route::get('/{slug}/members', [PublicGuildController::class, 'members']);
        Route::get('/{slug}/history', [PublicGuildController::class, 'history']);
    });

    Route::prefix('guilds')->group(function () {
        Route::get('/invite-info/{slug}', [GuildController::class, 'inviteInfo']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/apply/{slug}', [GuildController::class, 'apply']);
            Route::delete('/my/application', [GuildController::class, 'cancelApplication']);
            Route::post('/my/leave', [GuildController::class, 'leave']);
            Route::post('/my/logo', [GuildController::class, 'logo']);
            
            Route::middleware('role:creator')->group(function () {
                Route::patch('/my/invite-slug', [GuildController::class, 'updateInviteSlug']);
                Route::patch('/my/privacy', [GuildController::class, 'updatePrivacy']);
            });
        });
    });

    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'basicLogin']);
        Route::post('/telegram/init', [AuthController::class, 'initTelegramDeepLink']);
        Route::get('/telegram/check/{code}', [AuthController::class, 'checkTelegramDeepLink']);
        Route::post('/telegram/login', [AuthController::class, 'loginViaTelegram']);
        Route::post('/telegram/verify', [AuthController::class, 'tmaVerify']);
        Route::post('/telegram/register', [AuthController::class, 'tmaRegister']);
        Route::post('/login/{provider}', [AuthController::class, 'login']);
        Route::get('/redirect/discord', [AuthController::class, 'redirect']);
        Route::get('/callback/discord', [AuthController::class, 'callback']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::get('/telegram-link', [AuthController::class, 'telegramLink']);
            Route::post('/social/link-init', [AuthController::class, 'initSocialLink']);
            Route::post('/telegram/link', [AuthController::class, 'tmaLink']);
            Route::patch('/profile', [AuthController::class, 'updateProfile']);
            Route::patch('/account', [AuthController::class, 'updateAccount']);
            Route::delete('/linked-accounts/{provider}', [AuthController::class, 'unlinkAccount']);
            Route::post('/avatar', [AuthController::class, 'avatar']);
            Route::post('/logout', [AuthController::class, 'logout']);

            // Gear
            Route::get('/gear', [GearController::class, 'index']);
            Route::post('/gear/media', [GearController::class, 'storeMedia']);
            Route::delete('/gear/media/{id}', [GearController::class, 'destroyMedia']);
        });
    });

    Route::middleware(['auth:sanctum', 'onboarded'])->group(function () {
        Route::post('/guilds', [V1GuildController::class, 'store']);
        Route::post('/invites/{token}/accept', [GuildInviteController::class, 'accept']);

        Route::middleware('active_member')->group(function () {
            // Dashboard
            Route::get('/dashboard/member', [DashboardController::class, 'memberView']);

            // Member permissions (implicitly by active_member)
            Route::get('/invites/{token}', [GuildInviteController::class, 'show']);
            Route::get('/guilds/my/members', [GuildMemberController::class, 'index']);
            Route::get('/guilds/my/invite', [GuildInviteController::class, 'myInvite']);
            Route::get('/guilds/my/telegram-bind-token', [V1GuildController::class, 'telegramBindToken']);
            Route::post('/guilds/my/verification/submit', [GuildVerificationController::class, 'submit']);

            // Knowledge Base (Guides)
            Route::get('/guilds/my/posts', [GuildPostController::class, 'index']);
            Route::get('/guilds/my/posts/{id}', [GuildPostController::class, 'show']);

            // User Profile
            Route::get('/users/{id}/profile', [UserController::class, 'show']);

            // Events
            Route::get('/events', [EventController::class, 'index']);
            Route::get('/events/{id}', [EventController::class, 'show']);
            Route::post('/events/{id}/join', [EventParticipantController::class, 'join']);
            Route::post('/events/{id}/decline', [EventParticipantController::class, 'decline']);

            // Officer permissions
            Route::middleware('role:officer')->group(function () {
                Route::get('/dashboard/analytics', [DashboardController::class, 'analytics']);
                Route::get('/guilds/my/integrations', [GuildIntegrationController::class, 'index']);
                Route::patch('/guilds/my/integrations/{provider}', [GuildIntegrationController::class, 'update']);
                Route::delete('/guilds/my/integrations/{provider}', [GuildIntegrationController::class, 'destroy']);
                Route::post('/guilds/{guild}/invites', [GuildInviteController::class, 'store']);
                Route::post('/events', [EventController::class, 'store']);
                Route::patch('/events/{id}', [EventController::class, 'update']);
                Route::post('/events/{id}/publish', [EventController::class, 'publish']);
                Route::post('/events/{id}/cancel', [EventController::class, 'cancel']);
                Route::post('/events/{id}/archive', [EventController::class, 'archive']);
                Route::patch('/events/{id}/participants/{user_id}', [EventParticipantController::class, 'move']);
                Route::post('/events/{id}/squads', [EventSquadController::class, 'store']);
                Route::patch('/events/{id}/squads/reorder', [EventSquadController::class, 'reorder']);
                Route::patch('/events/{eventId}/squads/{squadId}', [EventSquadController::class, 'update']);
                Route::delete('/events/{eventId}/squads/{squadId}', [EventSquadController::class, 'destroy']);

                // Verifications
                Route::get('/guilds/my/verifications', [GuildVerificationController::class, 'index']);
                Route::get('/guilds/my/verifications/{userId}', [GuildVerificationController::class, 'show']);
                Route::post('/guilds/my/verifications/{userId}/approve', [GuildVerificationController::class, 'approve']);
                Route::post('/guilds/my/verifications/{userId}/reject', [GuildVerificationController::class, 'reject']);
            });

            // Admin permissions
            Route::middleware('role:admin')->group(function () {
                Route::get('/guilds/my/applications', [GuildApplicationController::class, 'index']);
                Route::post('/guilds/my/applications/{user_id}/approve', [GuildApplicationController::class, 'approve']);
                Route::post('/guilds/my/applications/{user_id}/reject', [GuildApplicationController::class, 'reject']);
                Route::patch('/guilds/my/members/{user_id}/role', [GuildMemberController::class, 'updateRole']);
                Route::delete('/guilds/my/members/{user_id}', [GuildMemberController::class, 'destroy']);

                // Knowledge Base (Guides) management
                Route::post('/guilds/my/posts', [GuildPostController::class, 'store']);
                Route::patch('/guilds/my/posts/reorder', [GuildPostController::class, 'reorder']);
                Route::put('/guilds/my/posts/{id}', [GuildPostController::class, 'update']);
                Route::delete('/guilds/my/posts/{id}', [GuildPostController::class, 'destroy']);
                Route::post('/guilds/my/posts/media', [GuildPostController::class, 'uploadMedia']);
            });

        });
    });

    // Discord Internal API
    Route::prefix('discord')->group(function () {
        Route::prefix('guilds')->group(function () {
            Route::post('/presets', [GuildPresetController::class, 'store']);
            Route::delete('/presets/{id}', [GuildPresetController::class, 'destroy']);
            Route::post('/', [DiscordGuildController::class, 'store']);
            Route::get('/{guild_id}/presets', [GuildPresetController::class, 'index']);
            Route::get('/{discord_id}', [DiscordGuildController::class, 'show']);
            Route::post('/{discord_guild_id}/access-check', [DiscordGuildController::class, 'checkAccess']);
        });

        Route::prefix('events')->group(function () {
            Route::post('/', [EventController::class, 'store']);
            Route::get('{id}', [EventController::class, 'show']);
            Route::post('{id}/publish', [EventController::class, 'publish']);
            Route::post('{id}/cancel', [EventController::class, 'cancel']);
            Route::patch('{id}/message', [EventController::class, 'updateMessageId']);
            Route::post('{id}/participants', [EventParticipantController::class, 'store']);
            Route::post('{id}/squads', [EventSquadController::class, 'store']);
            Route::post('{id}/squads/reorder', [EventSquadController::class, 'reorder']);
            Route::post('{id}/apply-preset', [EventController::class, 'applyPreset']);
            Route::delete('{id}/squads/{squadId}', [EventSquadController::class, 'destroy']);
        });

        Route::prefix('squads')->group(function () {
            Route::post('{squad_id}/presets', [EventSquadController::class, 'applyPreset']);
        });

        Route::post('users/sync', [UserController::class, 'sync']);
    });
});
