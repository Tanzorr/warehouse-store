<?php

namespace App\Domain\Order;

readonly class OrderPaymentConfirmed
{
    public function __construct(
        public int    $orderId,
        public int    $userId,
        public string $reservationExternalId,
        public float  $totalPrice,
        public \DateTimeImmutable $occurredAt,
    ) {}
}
