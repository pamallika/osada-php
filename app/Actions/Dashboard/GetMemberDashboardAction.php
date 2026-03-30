<?php

namespace App\Actions\Dashboard;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\User;
use Illuminate\Support\Collection;

class GetMemberDashboardAction
{
    /**
     * Get aggregated data for the member dashboard.
     *
     * @param User $user
     * @return array
     */
    public function execute(User $user): array
    {
        // 1. Stats: Sieges attended
        $siegesAttended = EventParticipant::query()
            ->where('user_id', $user->id)
            ->where('status', 'confirmed')
            ->whereHas('event', function ($query) {
                $query->where('status', 'completed');
            })
            ->count();

        // 2. Guild: Current user's guild
        $guildMembership = $user->guildMemberships()
            ->where('status', 'active')
            ->with(['guild'])
            ->first();
        
        $guild = $guildMembership?->guild;

        // 3. Next Event (Nearest upcoming event user is participating in)
        $nextEvent = null;
        if ($guild) {
            $nextEvent = Event::query()
                ->where('guild_id', $guild->id) // Important: stay within guild context
                ->where('start_at', '>', now())
                ->whereHas('participants', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->with(['squads']) // include squads as per requirement
                ->orderBy('start_at', 'asc')
                ->first();
                
            // If the user participated, we might want their specific status too
            if ($nextEvent) {
                $myParticipation = $nextEvent->participants()
                    ->where('user_id', $user->id)
                    ->first();
                
                // We add custom attribute for FE to know user's status easily if needed
                // though it's not explicitly in the contract, it's good practice.
                // But the contract says "next_event: Event | null".
            }
        }

        // 4. Open Events (Published events in the guild where user is NOT a participant)
        $openEvents = new Collection();
        if ($guild) {
            $openEvents = Event::query()
                ->where('guild_id', $guild->id)
                ->where('status', 'published')
                ->whereDoesntHave('participants', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->orderBy('start_at', 'asc')
                ->get();
        }

        return [
            'stats' => [
                'sieges_attended' => $siegesAttended,
            ],
            'guild' => $guild,
            'next_event' => $nextEvent,
            'open_events' => $openEvents,
        ];
    }
}
