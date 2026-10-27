<?php

namespace App\Infrastructure\Persistence\Order;

use App\Domain\Identity\ValueObjects\UserId;
use App\Domain\Order\Order as DomainOrder;
use App\Domain\Order\OrderItem;
use App\Domain\Order\OrderStatus;
use App\Domain\Order\ValueObjects\OrderId;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Shared\ValueObjects\Address;
use App\Domain\Shared\ValueObjects\Money;
use App\Domain\Shared\ValueObjects\Quantity;
use App\Models\Order as EloquentOrder;
use App\Models\OrderItem as EloquentOrderItem;

class OrderMapper
{
    public function toDomain(EloquentOrder $model): DomainOrder
    {
        $items = $model->orderItems->map(fn (EloquentOrderItem $item) => OrderItem::create(
            productId: new ProductId($item->product_id),
            price: new Money((float) $item->price),
            quantity: new Quantity($item->quantity),
        ))->all();

        return DomainOrder::reconstitute(
            id: new OrderId($model->id),
            userId: new UserId($model->user_id),
            status: $model->status,
            paymentStatus: OrderStatus::from($model->payment_status),
            totalPrice: new Money((float) $model->total_price),
            shippingAddress: new Address($model->shipping_address),
            billingAddress: $model->billing_address ? new Address($model->billing_address) : null,
            notes: $model->notes,
            reservationId: $model->reservation_id,
            items: $items,
        );
    }

    /** @return array<string, mixed> */
    public function toPersistence(DomainOrder $order): array
    {
        return [
            'id' => $order->getId()->value,
            'user_id' => $order->getUserId()->value,
            'status' => $order->getStatus(),
            'payment_status' => $order->getPaymentStatus()->value,
            'total_price' => $order->calculateTotal()->amount,
            'shipping_address' => $order->getShippingAddress()->value,
            'billing_address' => $order->getBillingAddress()?->value,
            'notes' => $order->getNotes(),
            'reservation_id' => $order->getReservationId(),
        ];
    }
}
