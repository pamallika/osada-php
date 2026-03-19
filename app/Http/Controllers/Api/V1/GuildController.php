<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Actions\Guilds\CreateGuildAction;
use App\Actions\Guilds\ApplyToGuildAction;
use App\Actions\Guilds\LeaveOrDeactivateGuildAction;
use App\Http\Requests\Api\V1\Guild\StoreGuildRequest;
use App\Http\Resources\Api\V1\GuildResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Traits\ApiResponser;
use App\Models\Guild;
use App\Actions\Telegram\GenerateTelegramBindingLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class GuildController extends Controller
{
    use ApiResponser;

    public function inviteInfo(string $slug): JsonResponse
    {
        $slug = strtolower($slug);
        $guild = Guild::where('invite_slug', $slug)->first();

        if (!$guild) {
            return $this->errorResponse('Guild not found.', 404);
        }

        if ($guild->status !== 'active') {
            return $this->errorResponse('This guild is no longer active.', 404);
        }

        return $this->successResponse([
            'id' => $guild->id,
            'name' => $guild->name,
            'logo_url' => $guild->logo_url,
            'members_count' => $guild->members()->where('status', 'active')->count(),
        ]);
    }

    public function apply(string $slug, Request $request, ApplyToGuildAction $action): JsonResponse
    {
        $action->execute($request->user(), strtolower($slug));

        return $this->successResponse(null, 'Application submitted successfully');
    }

    public function cancelApplication(Request $request): JsonResponse
    {
        $membership = $request->user()->guildMemberships()
            ->where('status', 'pending')
            ->first();

        if (!$membership) {
            return $this->errorResponse('No pending application found.', 404);
        }

        $membership->delete();

        return $this->successResponse(null, 'Application cancelled successfully');
    }

    public function leave(Request $request, LeaveOrDeactivateGuildAction $action): JsonResponse
    {
        $user = $action->execute($request->user());

        return $this->successResponse(new UserResource($user), 'Successfully left the guild');
    }

    public function updateInviteSlug(Request $request): JsonResponse
    {
        $request->merge([
            'invite_slug' => strtolower($request->invite_slug)
        ]);

        $request->validate([
            'invite_slug' => 'required|string|alpha_dash|unique:guilds,invite_slug',
        ]);

        $membership = $request->user()->guildMemberships()
            ->where('status', 'active')
            ->where('role', 'creator')
            ->first();

        if (!$membership) {
            return $this->errorResponse('Only the guild creator can update the invite slug.', 403);
        }

        $guild = $membership->guild;
        $guild->update(['invite_slug' => $request->invite_slug]);

        return $this->successResponse(new GuildResource($guild), 'Invite slug updated successfully');
    }

    public function telegramBindToken(Request $request, GenerateTelegramBindingLink $action)
    {
        // В этом контроллере уже есть проверка active_member из роутов
        $membership = $request->user()->guildMemberships()->where('status', 'active')->firstOrFail();
        
        $token = $action->execute(null, $membership->guild_id);
        
        return $this->successResponse(['token' => $token]);
    }

    public function store(StoreGuildRequest $request, CreateGuildAction $action)
    {
        $guild = $action->execute($request->user(), $request->validated());

        return $this->successResponse(new GuildResource($guild), 'Guild created successfully', 201);
    }
}
