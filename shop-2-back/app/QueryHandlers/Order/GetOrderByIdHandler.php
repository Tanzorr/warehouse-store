<?php

namespace App\QueryHandlers\Order;

use App\Models\Order;
use App\Queries\Order\GetOrderByIdQuery;

class GetOrderByIdHandler
{
    public function handle(GetOrderByIdQuery $query): Order
    {
        return Order::with('orderItems')->findOrFail($query->orderId);
    }
}
