<?php

namespace App\Actions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetSystemStatusAction
{
    /**
     * Handle the incoming request.
     *
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'version' => '1.0.0',
            'timestamp' => now(),
        ]);
    }
}
