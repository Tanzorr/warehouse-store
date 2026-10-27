<?php

namespace App\Domain\Shared\ValueObjects;

final class Money
{
    public readonly float $amount;

    public function __construct(float $amount)
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Money amount cannot be negative, got '.$amount);
        }

        $this->amount = round($amount, 2);
    }

    public static function zero(): self
    {
        return new self(0.0);
    }

    public function add(Money $other): self
    {
        return new self($this->amount + $other->amount);
    }

    public function multiply(int $factor): self
    {
        return new self($this->amount * $factor);
    }

    public function isGreaterThan(Money $other): bool
    {
        return $this->amount > $other->amount;
    }

    public function equals(Money $other): bool
    {
        return $this->amount === $other->amount;
    }

    public function __toString(): string
    {
        return number_format($this->amount, 2);
    }
}
