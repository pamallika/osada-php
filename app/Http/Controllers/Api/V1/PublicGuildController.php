<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Guild;
use App\Models\GuildMembershipHistory;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicGuildController extends Controller
{
    use ApiResponser;

    /**
     * List all public guilds.
     */
    public function index(): JsonResponse
    {
        $guilds = Guild::where('is_public', true)
            ->where('status', 'active')
            ->withCount(['members' => function ($query) {
                $query->where('status', 'active');
            }])
            ->get();

        return $this->successResponse($guilds->map(fn($g) => [
            'id' => $g->id,
            'name' => $g->name,
            'invite_slug' => $g->invite_slug,
            'logo_url' => $g->logo_url ? asset($g->logo_url) : null,
            'members_count' => $g->members_count,
        ]));
    }

    /**
     * Show public guild profile.
     */
    public function show(string $slug): JsonResponse
    {
        $guild = Guild::where('invite_slug', $slug)
            ->where('is_public', true)
            ->where('status', 'active')
            ->with(['owner.profile'])
            ->withCount(['members' => function ($query) {
                $query->where('status', 'active');
            }])
            ->firstOrFail();

        return $this->successResponse([
            'id' => $guild->id,
            'name' => $guild->name,
            'invite_slug' => $guild->invite_slug,
            'logo_url' => $guild->logo_url ? asset($guild->logo_url) : null,
            'creator_family_name' => $guild->owner?->profile?->family_name,
            'members_count' => $guild->members_count,
        ]);
    }

    /**
     * List public guild members.
     */
    public function members(string $slug): JsonResponse
    {
        $guild = Guild::where('invite_slug', $slug)
            ->where('is_public', true)
            ->where('status', 'active')
            ->firstOrFail();

        $members = $guild->members()
            ->where('status', 'active')
            ->with(['user.profile'])
            ->get();

        return $this->successResponse($members->map(fn($m) => [
            'family_name' => $m->user?->profile?->family_name,
            'joined_at' => $m->joined_at,
            'days_in_guild' => $m->joined_at ? (int) floor(\Carbon\Carbon::parse($m->joined_at)->diffInDays(now())) : 0,
            'avatar_url' => $m->user?->avatar_url ? (str_starts_with($m->user->avatar_url, 'http') ? $m->user->avatar_url : asset($m->user->avatar_url)) : null,
        ]));
    }

    /**
     * Show guild membership history.
     */
    public function history(string $slug): JsonResponse
    {
        $guild = Guild::where('invite_slug', $slug)
            ->where('is_public', true)
            ->where('status', 'active')
            ->firstOrFail();

        $history = $guild->membershipHistories()
            ->with(['user.profile'])
            ->latest('created_at')
            ->limit(50)
            ->get();

        return $this->successResponse($history->map(fn($h) => [
            'action' => $h->action,
            'family_name' => $h->user?->profile?->family_name,
            'created_at' => $h->created_at,
            'avatar_url' => $h->user?->avatar_url ? (str_starts_with($h->user->avatar_url, 'http') ? $h->user->avatar_url : asset($h->user->avatar_url)) : null,
        ]));
    }
}
