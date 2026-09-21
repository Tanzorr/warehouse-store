<?php

namespace App\Domain\Shared\ValueObjects;

final class Address
{
    public readonly string $value;

    public function __construct(string $value)
    {
        if (trim($value) === '') {
            throw new \InvalidArgumentException('Address cannot be empty');
        }

        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
