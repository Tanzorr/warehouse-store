<?php

namespace App\Application\Query\GetReservations;

readonly class ReservationItem
{
    public function __construct(
        public int     $id,
        public string  $status,
        public string  $comment,
        public string  $reservedAt,
        public ?string $releasedAt,
        /** @var array{productId: int, name: string, sku: string, amount: int}[] */
        public array   $items,
        public int     $totalItems,
    ) {}
}
