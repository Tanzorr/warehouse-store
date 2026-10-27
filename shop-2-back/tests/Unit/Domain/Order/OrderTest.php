<?php

namespace Tests\Unit\Domain\Order;

use App\Domain\Identity\ValueObjects\UserId;
use App\Domain\Order\Events\OrderCreated;
use App\Domain\Order\Order;
use App\Domain\Order\ValueObjects\OrderId;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Shared\ValueObjects\Address;
use App\Domain\Shared\ValueObjects\Money;
use App\Domain\Shared\ValueObjects\Quantity;
use Tests\TestCase;

class OrderTest extends TestCase
{
    private function makeOrder(): Order
    {
        return Order::create(
            id: new OrderId(1),
            userId: new UserId(1),
            status: 'pending',
            totalPrice: new Money(0),
            shippingAddress: new Address('123 Main St'),
        );
    }

    public function test_create_records_order_created_event(): void
    {
        $order = $this->makeOrder();

        $events = $order->pullEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(OrderCreated::class, $events[0]);
    }

    public function test_add_item_records_event_and_appends_item(): void
    {
        $order = $this->makeOrder();
        $order->pullEvents();

        $order->addItem(new ProductId(1), new Money(10), new Quantity(2));

        $this->assertCount(1, $order->getItems());
        $this->assertCount(1, $order->pullEvents());
    }

    public function test_calculate_total_sums_item_subtotals(): void
    {
        $order = $this->makeOrder();
        $order->addItem(new ProductId(1), new Money(10), new Quantity(2));
        $order->addItem(new ProductId(2), new Money(5), new Quantity(3));

        $this->assertSame(35.0, $order->calculateTotal()->amount);
    }

    public function test_mark_as_paid_twice_throws(): void
    {
        $order = $this->makeOrder();
        $order->addItem(new ProductId(1), new Money(10), new Quantity(2));

        $order->markAsPaid('reservation-1');

        $this->expectException(\DomainException::class);

        $order->markAsPaid('reservation-1');
    }

    public function test_add_item_throws_after_order_is_paid(): void
    {
        $order = $this->makeOrder();
        $order->addItem(new ProductId(1), new Money(10), new Quantity(2));
        $order->markAsPaid('reservation-1');

        $this->expectException(\DomainException::class);

        $order->addItem(new ProductId(2), new Money(5), new Quantity(1));
    }
}
