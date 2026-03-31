<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserGearMediaResource;
use App\Http\Resources\Api\V1\UserProfileResource;
use App\Models\UserGearMedia;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

use App\Actions\Auth\UpdateUserGearMediaAction;
use App\Http\Requests\Api\V1\Auth\UpdateGearMediaRequest;

class GearController extends Controller
{
    use ApiResponser;

    /**
     * Get the current user's profile and filtered gear media.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $mandatoryLabels = ['crystal', 'relic', 'zakalk', 'gear'];
        
        $media = $user->gearMedia()
            ->whereIn('label', $mandatoryLabels)
            ->get();

        return $this->successResponse([
            'profile' => new UserProfileResource($user->profile),
            'media' => UserGearMediaResource::collection($media),
        ]);
    }

    /**
     * Upload a gear screenshot.
     */
    public function storeMedia(UpdateGearMediaRequest $request, UpdateUserGearMediaAction $action): JsonResponse
    {
        $media = $action->execute(
            $request->user(), 
            $request->file('file'), 
            $request->input('label')
        );

        return $this->successResponse(
            new UserGearMediaResource($media), 
            'Media uploaded successfully', 
            201
        );
    }

    public function destroyMedia(int $id, Request $request): JsonResponse
    {
        $media = UserGearMedia::where('user_id', $request->user()->id)->findOrFail($id);
        
        // Extract relative path from URL
        $path = str_replace(Storage::url(''), '', $media->url);
        Storage::disk('public')->delete($path);
        
        $media->delete();

        // Reset verification status
        $membership = $request->user()->guildMemberships()
            ->where('status', 'active')
            ->first();

        if ($membership) {
            $membership->update([
                'verification_status' => 'incomplete',
                'verified_at' => null,
                'verified_by' => null,
            ]);
        }

        return $this->successResponse(null, 'Media deleted');
    }
}
