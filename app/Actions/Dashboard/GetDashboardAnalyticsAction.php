<?php

namespace App\Actions\Dashboard;

use App\Models\Event;
use App\Models\GuildMember;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class GetDashboardAnalyticsAction
{
    public function execute(User $user, int $period = 7): array
    {
        $membership = $user->guildMemberships()->where('status', 'active')->firstOrFail();
        $guildId = $membership->guild_id;
        $role = $membership->role;

        $cacheKey = "guild_{$guildId}_analytics_{$period}_{$role}";

        // Use tags if supported, otherwise just remember
        $cache = Cache::store();
        if (method_exists($cache, 'tags')) {
            $cache = $cache->tags(["guild:{$guildId}"]);
        }

        return $cache->remember($cacheKey, 15 * 60, function () use ($guildId, $period, $role) {
            $startDate = Carbon::now()->subDays($period)->startOfDay();

            // 1. Activity: Fill Rate
            $events = Event::where('guild_id', $guildId)
                ->where('start_at', '>=', $startDate)
                ->whereIn('status', ['published', 'completed'])
                ->withCount(['participants' => function ($query) {
                    $query->where('status', 'confirmed');
                }])
                ->get();

            $totalConfirmed = $events->sum('participants_count');
            $totalSlots = $events->sum('total_slots');
            $fillRate = $totalSlots > 0 ? ($totalConfirmed / $totalSlots) * 100 : 0;

            // 2. Activity: Top Players (confirmed applications)
            $topPlayers = DB::table('event_participants')
                ->join('events', 'event_participants.event_id', '=', 'events.id')
                ->join('users', 'event_participants.user_id', '=', 'users.id')
                ->leftJoin('user_profiles', 'users.id', '=', 'user_profiles.user_id')
                ->leftJoin('linked_accounts', function ($join) {
                    $join->on('users.id', '=', 'linked_accounts.user_id')
                        ->where('linked_accounts.provider', '=', 'discord');
                })
                ->where('events.guild_id', $guildId)
                ->where('events.start_at', '>=', $startDate)
                ->where('event_participants.status', 'confirmed')
                ->select(
                    'users.id',
                    DB::raw("COALESCE(user_profiles.family_name, user_profiles.global_name, 'User_' || users.id) as name"),
                    DB::raw('count(event_participants.id) as confirmed_count'),
                    'linked_accounts.avatar'
                )
                ->groupBy('users.id', 'user_profiles.family_name', 'user_profiles.global_name', 'linked_accounts.avatar')
                ->orderByDesc('confirmed_count')
                ->limit(10)
                ->get();

            // 3. Meta: Class Distribution (current members)
            $classDistribution = DB::table('guild_members')
                ->join('user_profiles', 'guild_members.user_id', '=', 'user_profiles.user_id')
                ->where('guild_members.guild_id', $guildId)
                ->where('guild_members.status', 'active')
                ->whereNull('guild_members.deleted_at')
                ->select(
                    DB::raw("COALESCE(NULLIF(user_profiles.char_class, ''), 'Класс не указан') as class"),
                    DB::raw('count(*) as count')
                )
                ->groupBy('class')
                ->get();

            $data = [
                'activity' => [
                    'fill_rate' => round($fillRate, 1),
                    'top_players' => $topPlayers,
                ],
                'meta' => [
                    'class_distribution' => $classDistribution,
                ]
            ];

            // 4. HR: Dynamics (Admin/Creator only)
            if ($role === 'admin' || $role === 'creator') {
                $days = [];
                $joinedCounts = [];
                $leftCounts = [];

                for ($i = $period - 1; $i >= 0; $i--) {
                    $date = Carbon::now()->subDays($i)->format('Y-m-d');
                    $days[] = $date;

                    $joined = GuildMember::withTrashed()
                        ->where('guild_id', $guildId)
                        ->whereDate('created_at', $date)
                        ->count();

                    $left = GuildMember::onlyTrashed()
                        ->where('guild_id', $guildId)
                        ->whereDate('deleted_at', $date)
                        ->count();

                    $joinedCounts[] = $joined;
                    $leftCounts[] = $left;
                }

                $data['hr'] = [
                    'dynamics' => [
                        'dates' => $days,
                        'joined' => $joinedCounts,
                        'left' => $leftCounts,
                    ]
                ];
            }

            return $data;
        });
    }

    public static function invalidate(int $guildId): void
    {
        $cache = Cache::store();
        if (method_exists($cache, 'tags')) {
            $cache->tags(["guild:{$guildId}"])->flush();
        } else {
            foreach ([7, 14, 30] as $period) {
                foreach (['officer', 'admin', 'creator'] as $role) {
                    Cache::forget("guild_{$guildId}_analytics_{$period}_{$role}");
                }
            }
        }
    }
}
