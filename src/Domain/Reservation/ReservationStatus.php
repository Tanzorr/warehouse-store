<?php

namespace App\Domain\Reservation;

enum ReservationStatus: string
{
    case Pending   = 'pending';
    case Committed = 'committed';
    case Canceled  = 'canceled';

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending   => [self::Committed, self::Canceled],
            self::Committed => [],
            self::Canceled  => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }
}
