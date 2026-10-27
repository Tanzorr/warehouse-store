<?php

namespace App\Domain\Shared\ValueObjects;

abstract class IntId
{
    public function __construct(
        public readonly int $value,
    ) {
        if ($value <= 0) {
            throw new \InvalidArgumentException(
                static::class.' must be a positive integer, got '.$value
            );
        }
    }

    public function equals(self $other): bool
    {
        return static::class === get_class($other) && $this->value === $other->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
