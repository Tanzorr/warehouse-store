<?php

namespace App\Domain\Order;

use App\Domain\Identity\ValueObjects\UserId;
use App\Domain\Order\ValueObjects\OrderId;

interface OrderRepository
{
    /**
     * @throws \App\Domain\Shared\NotFoundException
     */
    public function findById(OrderId $id): Order;

    /** @return Order[] */
    public function findByUserId(UserId $userId): array;

    public function save(Order $order): void;

    public function delete(OrderId $id): void;

    public function nextId(): OrderId;
}
