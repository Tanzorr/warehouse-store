<?php

namespace App\Application\Query\GetReservations;

readonly class GetReservationsQuery
{
    public function __construct(
        public ?string $status = null,
        public ?int    $productId = null,
        public int     $page = 1,
        public int     $perPage = 20,
    ) {}
}
