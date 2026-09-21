<?php

namespace App\Contracts;

use App\Models\Media;
use Illuminate\Pagination\LengthAwarePaginator;

interface MediaServiceInterface
{
    public function getAllUserMedia($search = ''): LengthAwarePaginator;

    public function storeMedia($file): Media;

    public function deleteMedia(Media $media): void;

    public function attachMedia(mixed $entity, int $mediableId): void;

    public function deleteAllMedia(mixed $entity, int $mediableId): void;
}
