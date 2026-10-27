<?php

namespace App\Domain\Order\Events;

use App\Domain\Order\OrderStatus;
use App\Domain\Order\ValueObjects\OrderId;

readonly class OrderStatusChanged
{
    public function __construct(
        public OrderId $orderId,
        public OrderStatus $from,
        public OrderStatus $to,
        public \DateTimeImmutable $occurredAt,
    ) {}
}
