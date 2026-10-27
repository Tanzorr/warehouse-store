<?php

namespace App\CommandHandlers\Order;

use App\Commands\Order\DeleteOrderCommand;
use App\Domain\Order\OrderRepository;

class DeleteOrderHandler
{
    public function __construct(
        private readonly OrderRepository $repository,
    ) {}

    public function handle(DeleteOrderCommand $command): bool
    {
        $this->repository->findById($command->orderId);
        $this->repository->delete($command->orderId);

        return true;
    }
}
