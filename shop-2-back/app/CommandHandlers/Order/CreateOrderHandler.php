<?php

namespace App\CommandHandlers\Order;

use App\Commands\Order\CreateOrderCommand;
use App\Domain\Order\Order as OrderAggregate;
use App\Domain\Order\OrderRepository;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Shared\DomainEventDispatcher;
use App\Domain\Shared\ValueObjects\Money;
use App\Domain\Shared\ValueObjects\Quantity;
use App\Models\Order;

class CreateOrderHandler
{
    public function __construct(
        private readonly OrderRepository $repository,
        private readonly DomainEventDispatcher $eventDispatcher,
    ) {}

    public function handle(CreateOrderCommand $command): Order
    {
        $aggregate = OrderAggregate::create(
            id: $this->repository->nextId(),
            userId: $command->userId,
            status: $command->status,
            totalPrice: $command->totalPrice,
            shippingAddress: $command->shippingAddress,
            billingAddress: $command->billingAddress,
            notes: null,
        );

        foreach ($command->items as $item) {
            $aggregate->addItem(
                new ProductId($item['product_id']),
                new Money((float) $item['price']),
                new Quantity($item['quantity']),
            );
        }

        $this->repository->save($aggregate);
        $this->eventDispatcher->dispatch($aggregate);

        return Order::with('orderItems')->findOrFail($aggregate->getId()->value);
    }
}
