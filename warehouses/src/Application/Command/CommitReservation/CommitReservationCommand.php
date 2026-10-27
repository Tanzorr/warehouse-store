<?php

namespace App\Application\Command\CommitReservation;

readonly class CommitReservationCommand
{
    public function __construct(
        public string $reservationId,
    ) {}
}
