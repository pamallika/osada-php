<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Dashboard\GetDashboardAnalyticsAction;
use App\Actions\Dashboard\GetMemberDashboardAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\EventResource;
use App\Http\Resources\Api\V1\GuildResource;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponser;

    /**
     * Get aggregated data for the member dashboard.
     */
    public function memberView(Request $request, GetMemberDashboardAction $action): JsonResponse
    {
        $data = $action->execute($request->user());

        return $this->successResponse([
            'stats' => $data['stats'],
            'guild' => $data['guild'] ? new GuildResource($data['guild']) : null,
            'next_event' => $data['next_event'] ? new EventResource($data['next_event']) : null,
            'open_events' => EventResource::collection($data['open_events']),
        ]);
    }

    /**
     * Get analytics data for officers and admins.
     */
    public function analytics(Request $request, GetDashboardAnalyticsAction $action): JsonResponse
    {
        $validated = $request->validate([
            'period' => 'sometimes|integer|in:7,14,30'
        ]);

        $period = $validated['period'] ?? 7;

        $data = $action->execute($request->user(), (int) $period);

        return $this->successResponse($data);
    }
}
