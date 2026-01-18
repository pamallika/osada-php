<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UserSyncRequest;
use App\Actions\Discord\SyncDiscordUserAction;
use App\Traits\ApiResponser;

class UserController extends Controller
{
    use ApiResponser;

    public function sync(UserSyncRequest $request, SyncDiscordUserAction $action)
    {
        $user = $action->execute($request->validated());
        return $this->successResponse($user, 'User synchronized');
    }
}
