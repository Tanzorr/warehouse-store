<?php

namespace App\CommandHandlers\Order;

use App\Commands\Order\UpdateOrderCommand;
use App\Models\Order;

class UpdateOrderHandler
{
    public function handle(UpdateOrderCommand $command): Order
    {
        $order = Order::findOrFail($command->orderId->value);

        $order->update(array_filter([
            'status' => $command->status,
            'payment_status' => $command->paymentStatus,
            'total_price' => $command->totalPrice?->amount,
            'shipping_address' => $command->shippingAddress?->value,
            'billing_address' => $command->billingAddress?->value,
            'notes' => $command->notes,
        ], fn ($value) => $value !== null));

        return $order;
    }
}
