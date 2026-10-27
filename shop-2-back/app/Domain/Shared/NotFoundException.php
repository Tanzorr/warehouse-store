<?php

namespace App\Domain\Shared;

class NotFoundException extends \DomainException
{
    public static function forId(string $type, int|string $id): self
    {
        return new self("{$type} with id '{$id}' not found");
    }
}
