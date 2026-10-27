<?php

namespace App\CommandHandlers\Order;

use App\Commands\Order\PayOrderCommand;
use App\Domain\Order\OrderRepository;
use App\Domain\Shared\DomainEventDispatcher;
use App\Models\Order;

class PayOrderHandler
{
    public function __construct(
        private readonly OrderRepository $repository,
        private readonly DomainEventDispatcher $eventDispatcher,
    ) {}

    public function handle(PayOrderCommand $command): Order
    {
        $aggregate = $this->repository->findById($command->orderId);

        $aggregate->markAsPaid($aggregate->getReservationId() ?? '');

        $this->repository->save($aggregate);
        $this->eventDispatcher->dispatch($aggregate);

        return Order::findOrFail($aggregate->getId()->value);
    }
}
