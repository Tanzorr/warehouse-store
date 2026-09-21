<?php

namespace App\Domain\Shared\ValueObjects;

final class Email
{
    public readonly string $value;

    public function __construct(string $value)
    {
        if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email address: {$value}");
        }

        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
