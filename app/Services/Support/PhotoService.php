<?php

namespace App\Services\Support;

use App\Models\Photo;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Home-visit and family-support photos: private disk, served only through a
 * permission-checked route (like student documents).
 */
class PhotoService
{
    protected const DISK = 'local';

    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function storeMany(Model $owner, array $files, string $kind, User $by): void
    {
        foreach ($files as $file) {
            $folder = 'photos/'.str($owner->getTable())->singular().'/'.$owner->getKey();

            $owner->photos()->create([
                'kind' => $kind,
                'file_path' => Storage::disk(self::DISK)->putFile($folder, $file),
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'uploaded_by' => $by->id,
            ]);
        }
    }

    public function exists(Photo $photo): bool
    {
        return Storage::disk(self::DISK)->exists($photo->file_path);
    }

    public function stream(Photo $photo): StreamedResponse
    {
        return Storage::disk(self::DISK)->response(
            $photo->file_path,
            $photo->original_name ?: basename($photo->file_path),
            ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store'],
            'inline',
        );
    }

    public function delete(Photo $photo): void
    {
        Storage::disk(self::DISK)->delete($photo->file_path);
        $photo->delete();
    }

    /**
     * Remove every photo of an owner (when the visit or support entry itself is deleted).
     */
    public function deleteAllFor(Model $owner): void
    {
        $owner->photos->each(fn (Photo $photo) => $this->delete($photo));
    }
}
