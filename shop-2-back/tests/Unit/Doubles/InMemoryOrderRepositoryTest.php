<?php

namespace Tests\Unit\Doubles;

use App\Domain\Identity\ValueObjects\UserId;
use App\Domain\Order\Order;
use App\Domain\Order\ValueObjects\OrderId;
use App\Domain\Shared\NotFoundException;
use App\Domain\Shared\ValueObjects\Address;
use App\Domain\Shared\ValueObjects\Money;
use Tests\Doubles\InMemoryOrderRepository;
use Tests\TestCase;

class InMemoryOrderRepositoryTest extends TestCase
{
    private function makeOrder(OrderId $id, UserId $userId): Order
    {
        return Order::create(
            id: $id,
            userId: $userId,
            status: 'pending',
            totalPrice: new Money(0),
            shippingAddress: new Address('123 Main St'),
        );
    }

    public function test_save_and_find_by_id(): void
    {
        $repository = new InMemoryOrderRepository;
        $order = $this->makeOrder(new OrderId(1), new UserId(1));

        $repository->save($order);

        $this->assertSame($order, $repository->findById(new OrderId(1)));
    }

    public function test_find_by_id_throws_when_missing(): void
    {
        $repository = new InMemoryOrderRepository;

        $this->expectException(NotFoundException::class);

        $repository->findById(new OrderId(99));
    }

    public function test_find_by_user_id(): void
    {
        $repository = new InMemoryOrderRepository;
        $order1 = $this->makeOrder(new OrderId(1), new UserId(1));
        $order2 = $this->makeOrder(new OrderId(2), new UserId(2));
        $repository->save($order1);
        $repository->save($order2);

        $found = $repository->findByUserId(new UserId(1));

        $this->assertCount(1, $found);
        $this->assertSame($order1, $found[0]);
    }

    public function test_delete_removes_order(): void
    {
        $repository = new InMemoryOrderRepository;
        $order = $this->makeOrder(new OrderId(1), new UserId(1));
        $repository->save($order);

        $repository->delete(new OrderId(1));

        $this->expectException(NotFoundException::class);
        $repository->findById(new OrderId(1));
    }

    public function test_next_id_increments(): void
    {
        $repository = new InMemoryOrderRepository;

        $this->assertSame(1, $repository->nextId()->value);
        $this->assertSame(2, $repository->nextId()->value);
    }
}
