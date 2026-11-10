<?php

namespace App\Services\Website;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageUploadService
{
    /**
     * Store an uploaded image on the public disk and return its public URL.
     * When no new file is given, the existing value is kept.
     *
     * @param  string  $folder  Sub-folder under storage/app/public
     * @param  ?string  $existing  Current stored URL to keep (and delete-replace)
     */
    public function store(?UploadedFile $file, string $folder, ?string $existing = null): ?string
    {
        if ($file === null) {
            return $existing;
        }

        $this->delete($existing);

        $path = $file->store($folder, 'public');

        return Storage::disk('public')->url($path);
    }

    /**
     * Delete a previously stored public image by its URL (best effort).
     */
    public function delete(?string $url): void
    {
        if (blank($url)) {
            return;
        }

        $path = str_replace('/storage/', '', parse_url($url, PHP_URL_PATH) ?? '');

        if ($path !== '' && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
