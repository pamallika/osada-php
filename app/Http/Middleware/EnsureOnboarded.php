<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboarded
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && (!$user->profile || empty($user->profile->family_name))) {
            return response()->json([
                'error' => 'ONBOARDING_REQUIRED',
                'message' => 'Please complete your profile to continue.'
            ], 403);
        }

        return $next($request);
    }
}
