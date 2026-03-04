<?php

namespace App\Actions;

use Illuminate\Http\JsonResponse;

class GetSystemStatusAction
{
    /**
     * Handle the incoming request.
     *
     * @return JsonResponse
     */
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'version' => '1.0.0',
            'timestamp' => now(),
        ]);
    }
}
