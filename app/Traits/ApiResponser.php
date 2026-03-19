<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponser
{
    /**
     * Стандартный успешный ответ
     */
    protected function successResponse($data, $message = null, $code = 200): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ], $code);
    }

    /**
     * Стандартный ответ с ошибкой
     */
    protected function errorResponse($message, $code, $errorCode = null): JsonResponse
    {
        $response = [
            'status' => 'error',
            'message' => $message,
            'data' => null
        ];

        if ($errorCode) {
            $response['error'] = $errorCode;
        }

        return response()->json($response, $code);
    }
}
