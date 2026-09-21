<?php

namespace App\Domain\Reservation\ValueObjects;

final class ReservationId
{
    public function __construct(
        public readonly int $value,
    ) {
        if ($value <= 0) {
            throw new \InvalidArgumentException('ReservationId must be positive, got '.$value);
        }
    }

    public function equals(ReservationId $other): bool
    {
        return $this->value === $other->value;
    }
}
