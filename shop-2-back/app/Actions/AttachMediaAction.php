<?php

namespace App\Actions;

use App\Contracts\MediaServiceInterface;
use App\Contracts\MutationActionInterface;
use App\Traits\ResolvesEntities;

class AttachMediaAction implements MutationActionInterface
{
    use ResolvesEntities;

    public function __construct(private MediaServiceInterface $mediaService) {}

    public function handle(array $data): mixed
    {
        $entity = $this->resolveEntity($data['mediable_type'], $data['mediable_id']);
        $this->mediaService->attachMedia($entity, $data['media_id']);

        return $entity;
    }
}
