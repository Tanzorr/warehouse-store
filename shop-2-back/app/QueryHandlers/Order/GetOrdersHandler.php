<?php

namespace App\QueryHandlers\Order;

use App\Models\Order;
use App\Queries\Order\GetOrdersQuery;
use Illuminate\Database\Eloquent\Collection;

class GetOrdersHandler
{
    public function handle(GetOrdersQuery $query): Collection
    {
        return Order::with('orderItems')->get();
    }
}
