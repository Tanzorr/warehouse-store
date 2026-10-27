<?php

namespace App\Application\Command\CancelReservation;

readonly class CancelReservationCommand
{
    public function __construct(
        public string $reservationId,
        public ?string $comment = null,
    ) {}
}
