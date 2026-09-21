<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(int $count = 100): void
    {
        Order::factory($count)->create()->each(function ($order) {
            OrderItem::factory(rand(1, 10))->create([
                'order_id' => $order->id,
            ]);
        });

        $orders = Order::with('orderItems')->get();

        foreach ($orders as $order) {
            $orderTotalPrice = $order->orderItems->sum(function ($orderItem) {
                return $orderItem->price * $orderItem->quantity;
            });
            $order->update([
                'total_price' => $orderTotalPrice,
            ]);
        }
    }
}
