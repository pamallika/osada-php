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

class GearController extends Controller
{
    use ApiResponser;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load(['profile', 'gearMedia']);

        return $this->successResponse([
            'profile' => new UserProfileResource($user->profile),
            'media' => UserGearMediaResource::collection($user->gearMedia),
        ]);
    }

    public function storeMedia(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|image|max:5120', // 5MB
            'label' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $file = $request->file('file');

        // Process image to webp
        $img = Image::read($file->getRealPath());
        $encoded = $img->toWebp(80);
        
        $fileName = 'gear/' . $user->id . '/' . uniqid() . '.webp';
        Storage::disk('public')->put($fileName, $encoded);

        $media = UserGearMedia::create([
            'user_id' => $user->id,
            'url' => Storage::url($fileName),
            'label' => $request->input('label'),
            'is_draft' => true,
            'size' => strlen($encoded),
        ]);

        return $this->successResponse(new UserGearMediaResource($media), 'Media uploaded as draft', 201);
    }

    public function destroyMedia(int $id, Request $request): JsonResponse
    {
        $media = UserGearMedia::where('user_id', $request->user()->id)->findOrFail($id);
        
        // Extract relative path from URL
        $path = str_replace(Storage::url(''), '', $media->url);
        Storage::disk('public')->delete($path);
        
        $media->delete();

        return $this->successResponse(null, 'Media deleted');
    }
}
