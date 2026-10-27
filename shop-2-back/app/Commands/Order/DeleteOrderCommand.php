<?php

namespace App\Commands\Order;

use App\Domain\Order\ValueObjects\OrderId;

readonly class DeleteOrderCommand
{
    public function __construct(
        public OrderId $orderId,
    ) {}
}
