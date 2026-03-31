<?php

namespace App\Actions\Guilds;

use App\Models\Guild;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class UploadGuildLogoAction
{
    /**
     * Upload a guild logo.
     */
    public function execute(Guild $guild, UploadedFile $file): Guild
    {
        // 1. Process image to webp
        $img = Image::read($file->getRealPath());
        $encoded = $img->toWebp(80);
        
        $fileName = 'guilds/' . $guild->id . '/logo_' . uniqid() . '.webp';

        // 2. Delete old logo if it exists
        if ($guild->logo_url) {
            $oldPath = str_replace(Storage::url(''), '', $guild->logo_url);
            Storage::disk('public')->delete($oldPath);
        }

        // 3. Save new file
        Storage::disk('public')->put($fileName, $encoded);

        // 4. Update guild
        $guild->update([
            'logo_url' => Storage::url($fileName),
        ]);

        return $guild;
    }
}
