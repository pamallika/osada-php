<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Guild;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use App\Actions\Discord\SyncGuildAction;
use Illuminate\Support\Facades\Log;
use Throwable;

class DiscordGuildController extends Controller
{
    use ApiResponser;

    /**
     * Sync guild data from Discord bot.
     */
    public function store(Request $request, SyncGuildAction $action)
    {
        Log::info('Discord Guild Sync Payload:', $request->all());

        try {
            $guild = $action->execute($request->all());
            return $this->successResponse($guild, 'Guild synced');
        } catch (Throwable $e) {
            Log::error('Discord Guild Sync Error: ' . $e->getMessage(), [
                'exception' => $e,
                'payload' => $request->all()
            ]);

            // Return 200 OK even on error to stop retry cycles from messengers/bots
            return response()->json([
                'status' => 'error',
                'message' => 'Internal Server Error during guild sync'
            ], 200);
        }
    }

    public function show($discord_id)
    {
        try {
            $guildId = \App\Models\GuildIntegration::where('provider', 'discord')
                ->where('platform_id', $discord_id)
                ->value('guild_id');
            if (!$guildId) throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
            $guild = Guild::findOrFail($guildId);
            return $this->successResponse($guild);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Guild not found', 404);
        } catch (Throwable $e) {
            return $this->errorResponse('Internal server error', 500);
        }
    }

    public function checkAccess(Request $request, $discord_guild_id)
    {
        try {
            $guildId = \App\Models\GuildIntegration::where('provider', 'discord')
                ->where('platform_id', $discord_guild_id)
                ->value('guild_id');
            return $this->successResponse(['has_access' => !!$guildId]);
        } catch (Throwable $e) {
            return $this->errorResponse('Internal server error', 500);
        }
    }
}
