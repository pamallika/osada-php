<?php

namespace App\Http\Middleware;

use App\Actions\Discord\SyncUserAction;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class BotProxyAuth
{
    public function __construct(protected SyncUserAction $syncUserAction) {}

    public function handle(Request $request, Closure $next): Response
    {
        $botToken = $request->bearerToken();
        $expectedToken = config('services.discord.bot_api_token');

        if (!$botToken || !$expectedToken || $botToken !== $expectedToken) {
            return $next($request);
        }

        $discordUserId = $request->header('X-Discord-User-Id');
        if (!$discordUserId) {
            return $next($request);
        }

        // Авторегистрация или поиск пользователя через существующий Action
        $user = $this->syncUserAction->execute([
            'discord_id' => $discordUserId,
            'username' => $request->header('X-Discord-User-Name', 'DiscordUser'),
            'global_name' => $request->header('X-Discord-User-Name', 'DiscordUser'),
            'avatar' => $request->header('X-Discord-User-Avatar', ''),
            'discord_guild_id' => $request->header('X-Discord-Guild-Id'),
        ]);

        // Устанавливаем пользователя для текущего запроса (Sanctum/Auth)
        Auth::setUser($user);

        return $next($request);
    }
}
