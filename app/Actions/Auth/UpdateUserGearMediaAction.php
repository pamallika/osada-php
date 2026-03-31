<?php

namespace App\Actions\Auth;

use App\Models\GuildMember;
use App\Models\User;
use App\Models\UserGearMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class UpdateUserGearMediaAction
{
    /**
     * Update or create gear media.
     */
    public function execute(User $user, UploadedFile $file, string $label): UserGearMedia
    {
        // 1. Process image to webp
        $img = Image::read($file->getRealPath());
        $encoded = $img->toWebp(80);
        
        $fileName = 'gear/' . $user->id . '/' . uniqid() . '.webp';

        // 2. Clear existing media with the same label (Only if it's a draft)
        $existingMedia = UserGearMedia::where('user_id', $user->id)
            ->where('label', $label)
            ->where('is_draft', true)
            ->get();

        foreach ($existingMedia as $media) {
            $oldPath = str_replace(Storage::url(''), '', $media->url);
            Storage::disk('public')->delete($oldPath);
            $media->delete();
        }

        // 3. Save new file
        Storage::disk('public')->put($fileName, $encoded);

        // 4. Create new media record
        $media = UserGearMedia::create([
            'user_id' => $user->id,
            'url' => Storage::url($fileName),
            'label' => $label,
            'is_draft' => true,
            'size' => strlen($encoded),
        ]);

        // 5. Update verification status if necessary
        $membership = $user->guildMemberships()
            ->where('status', 'active')
            ->first();

        if ($membership && $membership->verification_status === 'verified') {
            $membership->update(['verification_status' => 'updated']);
        }

        return $media;
    }
}
