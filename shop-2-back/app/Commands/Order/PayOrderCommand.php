<?php

namespace App\Commands\Order;

use App\Domain\Order\ValueObjects\OrderId;

readonly class PayOrderCommand
{
    public function __construct(
        public OrderId $orderId,
    ) {}
}
