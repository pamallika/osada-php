<?php

namespace App\Actions\Guilds;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class UploadPostMediaAction
{
    /**
     * Process and store post media.
     *
     * @param UploadedFile $file
     * @param int $guildId
     * @return string URL of the stored file
     */
    public function execute(UploadedFile $file, int $guildId): string
    {
        // 1. Process image to webp
        $img = Image::read($file->getRealPath());
        $encoded = $img->toWebp(80);
        
        $fileName = 'posts/' . $guildId . '/' . uniqid() . '.webp';

        // 2. Save file
        Storage::disk('public')->put($fileName, $encoded);

        // 3. Return absolute URL
        return asset(Storage::url($fileName));
    }
}
