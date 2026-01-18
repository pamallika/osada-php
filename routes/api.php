<?php

use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\EventParticipantController;
use App\Http\Controllers\Api\EventSquadController;
use App\Http\Controllers\Api\GuildController;
use App\Http\Controllers\Api\GuildPresetController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('discord')->group(function () {

    Route::prefix('guilds')->group(function () {
        Route::post('/presets', [GuildPresetController::class, 'store']);
        Route::delete('/presets/{id}', [GuildPresetController::class, 'destroy']);
        Route::post('/', [GuildController::class, 'store']);
        Route::get('/{guild_id}/presets', [GuildPresetController::class, 'index']);
        Route::get('/{discord_id}', [GuildController::class, 'show']);
        Route::post('/{discord_guild_id}/access-check', [GuildController::class, 'checkAccess']);
    });

    Route::prefix('events')->group(function () {
        Route::post('/', [EventController::class, 'store']);
        Route::get('{id}', [EventController::class, 'show']);
        Route::post('{id}/publish', [EventController::class, 'publish']);
        Route::post('{id}/cancel', [EventController::class, 'cancel']);
        Route::patch('{id}/message', [EventController::class, 'updateMessageId']);
        Route::post('{id}/participants', [EventParticipantController::class, 'store']);
        Route::post('{id}/squads', [EventSquadController::class, 'store']);
        Route::post('{id}/apply-preset', [EventController::class, 'applyPreset']);
        Route::delete('{id}/squads/{squadId}', [EventSquadController::class, 'destroy']);
    });

    Route::prefix('squads')->group(function () {
        Route::post('{squad_id}/presets', [EventSquadController::class, 'applyPreset']);
    });

    Route::post('users/sync', [UserController::class, 'sync']);
});
