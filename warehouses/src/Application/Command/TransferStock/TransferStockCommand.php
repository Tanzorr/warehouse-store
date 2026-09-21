<?php

namespace App\Application\Command\TransferStock;

readonly class TransferStockCommand
{
    public function __construct(
        public int $productId,
        public int $fromWarehouseId,
        public int $toWarehouseId,
        public int $quantity,
        public ?string $comment = null,
        public ?int $userId = null,
    ) {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be a positive integer');
        }
        if ($fromWarehouseId === $toWarehouseId) {
            throw new \InvalidArgumentException('Source and destination warehouses must differ');
        }
    }
}
