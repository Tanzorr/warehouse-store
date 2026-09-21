<?php

namespace App\Domain\Reservation;

use App\Domain\Reservation\ValueObjects\Quantity;

final class ReservationItem
{
    public function __construct(
        public readonly int $productId,
        public readonly Quantity $quantity,
    ) {
    }

}
