<?php

namespace App\Domain\Shared\ValueObjects;

final class Quantity
{
    public function __construct(
        public readonly int $value,
    ) {
        if ($value < 0) {
            throw new \InvalidArgumentException('Quantity cannot be negative, got '.$value);
        }
    }

    public function add(Quantity $other): Quantity
    {
        return new Quantity($this->value + $other->value);
    }

    public function subtract(Quantity $other): Quantity
    {
        return new Quantity($this->value - $other->value);
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
