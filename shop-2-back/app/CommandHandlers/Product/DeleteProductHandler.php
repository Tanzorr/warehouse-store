<?php

namespace App\CommandHandlers\Product;

use App\Commands\Product\DeleteProductCommand;
use App\Domain\Product\ProductRepository;

class DeleteProductHandler
{
    public function __construct(
        private readonly ProductRepository $repository,
    ) {}

    public function handle(DeleteProductCommand $command): bool
    {
        $this->repository->findById($command->productId);
        $this->repository->delete($command->productId);

        return true;
    }
}
