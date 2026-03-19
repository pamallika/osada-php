<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Enums\GuildRole;

class CheckRole
{
    private array $roles = [
        GuildRole::MEMBER => 1,
        GuildRole::OFFICER => 2,
        GuildRole::ADMIN => 3,
        GuildRole::CREATOR => 4,
        GuildRole::LEADER => 4,
    ];

    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (!$user) {
             return response()->json([
                'status' => 'error',
                'error' => 'UNAUTHENTICATED'
             ], 401);
        }

        $membership = $user->guildMemberships()->where('status', 'active')->first();

        if (!$membership) {
            return response()->json([
                'status' => 'error',
                'error' => 'ACTIVE_MEMBERSHIP_REQUIRED',
                'message' => 'You must be an active member of a guild.'
            ], 403);
        }

        $currentRoleWeight = $this->roles[$membership->role] ?? 0;
        $requiredRoleWeight = $this->roles[$role] ?? 999;

        if ($currentRoleWeight < $requiredRoleWeight) {
            return response()->json([
                'status' => 'error',
                'error' => 'INSUFFICIENT_PERMISSIONS',
                'message' => "This action requires {$role} role or higher."
            ], 403);
        }

        return $next($request);
    }
}
