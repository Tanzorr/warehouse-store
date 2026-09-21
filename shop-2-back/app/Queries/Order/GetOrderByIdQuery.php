<?php

namespace App\Queries\Order;

readonly class GetOrderByIdQuery
{
    public function __construct(
        public int $orderId,
    ) {}
}
