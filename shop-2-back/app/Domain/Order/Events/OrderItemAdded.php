<?php

namespace App\Domain\Order\Events;

use App\Domain\Order\ValueObjects\OrderId;
use App\Domain\Product\ValueObjects\ProductId;

readonly class OrderItemAdded
{
    public function __construct(
        public OrderId $orderId,
        public ProductId $productId,
        public \DateTimeImmutable $occurredAt,
    ) {}
}
