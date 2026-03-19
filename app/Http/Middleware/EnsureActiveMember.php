<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveMember
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
             return response()->json(['error' => 'UNAUTHENTICATED'], 401);
        }

        $membership = $user->guildMemberships()->where('status', 'active')->first();

        if (!$membership) {
            return response()->json([
                'error' => 'ACTIVE_MEMBERSHIP_REQUIRED',
                'message' => 'You must be an active member of a guild to perform this action.'
            ], 403);
        }

        return $next($request);
    }
}
