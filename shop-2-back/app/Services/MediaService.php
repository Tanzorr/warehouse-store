<?php

namespace App\Services;

use App\Contracts\MediaServiceInterface;
use App\Models\Media;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class MediaService implements MediaServiceInterface
{
    public function __construct(protected Guard $auth)
    {
    }

    /**
     * Get all media for the authenticated user.
     */
    public function getAllUserMedia($search = ''): LengthAwarePaginator
    {
        return Media::where('user_id', $this->auth->id())
            ->where('mime_type', 'like', 'image/%')
            ->filterBySearch($search)
            ->orderBy('created_at', 'desc')
            ->paginate(5);
    }

    /**
     * Store a new media file.
     */
    public function storeMedia($file): Media
    {
        $userId = $this->auth->id();
        $existingMedia = Media::whereUserId($userId)
            ->where('file_name', $file->getClientOriginalName())
            ->first();

        if ($existingMedia) {
            return $existingMedia;
        }

        $path = $file->store('uploads', 'public');

        return Media::create([
            'user_id' => $userId,
            'file_path' => config('app.url').'/storage/'.$path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);
    }

    /**
     * Delete a media file.
     */
    public function deleteMedia(Media $media): void
    {
        $this->removeFileFromStorage($media->file_path);
        $media->delete();
    }

    public function attachMedia($entity, $mediaId): void
    {
        $entity->media()->attach($mediaId);
    }

    public function deleteAllMedia($entity, $mediaId): void
    {
        $entity->media()->detach($mediaId);
    }

    private function removeFileFromStorage(string $filePath): void
    {
        $relativePath = str_replace(config('app.url').'/storage/', '', $filePath);
        if (Storage::disk('public')->exists($relativePath)) {
            Storage::disk('public')->delete($relativePath);
        }
    }
}
