<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class UpdateUserAvatarAction
{
    /**
     * Update the user's avatar.
     */
    public function execute(User $user, UploadedFile $file): User
    {
        // Process image to webp
        $img = Image::read($file->getRealPath());
        $encoded = $img->toWebp(80);
        
        $fileName = 'avatars/' . $user->id . '/' . uniqid() . '.webp';
        
        // Delete old avatar if it exists and is a custom one
        if ($user->avatar_url) {
            $oldPath = str_replace(Storage::url(''), '', $user->avatar_url);
            Storage::disk('public')->delete($oldPath);
        }

        Storage::disk('public')->put($fileName, $encoded);

        $user->update([
            'avatar_url' => Storage::url($fileName),
        ]);

        return $user;
    }
}
