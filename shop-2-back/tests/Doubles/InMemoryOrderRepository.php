<?php

namespace Tests\Doubles;

use App\Domain\Identity\ValueObjects\UserId;
use App\Domain\Order\Order;
use App\Domain\Order\OrderRepository;
use App\Domain\Order\ValueObjects\OrderId;
use App\Domain\Shared\NotFoundException;

class InMemoryOrderRepository implements OrderRepository
{
    /** @var array<int, Order> */
    private array $orders = [];

    private int $nextId = 1;

    public function findById(OrderId $id): Order
    {
        return $this->orders[$id->value]
            ?? throw NotFoundException::forId('Order', $id->value);
    }

    public function findByUserId(UserId $userId): array
    {
        return array_values(array_filter(
            $this->orders,
            fn (Order $order) => $order->getUserId()->equals($userId)
        ));
    }

    public function save(Order $order): void
    {
        $this->orders[$order->getId()->value] = $order;
    }

    public function delete(OrderId $id): void
    {
        unset($this->orders[$id->value]);
    }

    public function nextId(): OrderId
    {
        return new OrderId($this->nextId++);
    }
}
