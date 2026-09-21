<?php

namespace App\Domain\Reservation\Events;

use App\Domain\Reservation\ValueObjects\ReservationId;

final class ReservationCommitted
{
    public function __construct(
        public readonly ReservationId $reservationId,
        public readonly \DateTimeImmutable $occurredAt,
    ) {}
}
