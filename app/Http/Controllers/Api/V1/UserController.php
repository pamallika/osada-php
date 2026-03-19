<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use App\Actions\Discord\SyncUserAction;
use App\Actions\Users\GetPlayerProfileAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class UserController extends Controller
{
    use ApiResponser;

    /**
     * Sync user data from Discord bot.
     */
    public function sync(Request $request, SyncUserAction $action): JsonResponse
    {
        Log::info('Discord User Sync Payload:', $request->all());

        try {
            $user = $action->execute($request->all());
            return $this->successResponse($user, 'User synced');
        } catch (Throwable $e) {
            Log::error('Discord User Sync Error: ' . $e->getMessage(), [
                'exception' => $e,
                'payload' => $request->all()
            ]);
            
            // Return 200 OK even on error to stop retry cycles from messengers/bots
            return response()->json([
                'status' => 'error',
                'message' => 'Internal Server Error during user sync'
            ], 200);
        }
    }

    /**
     * Get player profile.
     */
    public function show(int $userId, GetPlayerProfileAction $action): JsonResponse
    {
        try {
            $user = $action->execute($userId);
            return $this->successResponse($user);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 404);
        } catch (Throwable $e) {
            return $this->errorResponse('Internal server error', 500);
        }
    }
}
