<?php

namespace App\Domain\Product\ValueObjects;

final class Sku
{
    public readonly string $value;

    public function __construct(string $value)
    {
        $value = trim($value);

        if ($value === '') {
            throw new \InvalidArgumentException('SKU cannot be empty');
        }

        if (mb_strlen($value) > 50) {
            throw new \InvalidArgumentException('SKU cannot be longer than 50 characters');
        }

        $this->value = $value;
    }

    public function equals(Sku $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
