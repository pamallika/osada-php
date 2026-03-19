<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\GuildIntegration;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class GuildIntegrationController extends Controller
{
    use ApiResponser;

    public function index(Request $request)
    {
        $membership = $request->user()->guildMemberships()->where('status', 'active')->firstOrFail();
        $integrations = $membership->guild->integrations;

        return $this->successResponse($integrations);
    }

    public function update(Request $request, $provider)
    {
        $membership = $request->user()->guildMemberships()->where('status', 'active')->firstOrFail();
        
        $validated = $request->validate([
            'platform_id' => 'nullable|string',
            'platform_title' => 'nullable|string',
            'announcement_channel_id' => 'nullable|string',
            'settings' => 'nullable|array',
        ]);

        // Check if this platform_id is already bound to another guild
        if (!empty($validated['platform_id'])) {
            $existing = GuildIntegration::where('provider', $provider)
                ->where('platform_id', $validated['platform_id'])
                ->where('guild_id', '!=', $membership->guild_id)
                ->first();

            if ($existing) {
                return $this->errorResponse('⚠️ Эта группа/сервер уже привязана к другой гильдии в SAGE. Чтобы перепривязать её, сначала удалите старую интеграцию в панели управления SAGE другой гильдии.', 422);
            }
        }

        $integration = GuildIntegration::updateOrCreate(
            ['guild_id' => $membership->guild_id, 'provider' => $provider],
            $validated
        );

        return $this->successResponse($integration, 'Integration updated successfully');
    }

    public function destroy(Request $request, $provider)
    {
        $membership = $request->user()->guildMemberships()->where('status', 'active')->firstOrFail();

        $integration = GuildIntegration::where('guild_id', $membership->guild_id)
            ->where('provider', $provider)
            ->firstOrFail();

        $integration->delete();

        return $this->successResponse(null, 'Integration deleted successfully');
    }
}
