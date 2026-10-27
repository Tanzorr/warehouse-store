<?php

namespace App\Application\Command\AdjustStock;

readonly class AdjustStockCommand
{
    public function __construct(
        public int $productId,
        public int $warehouseId,
        public int $quantity,
        public ?string $comment = null,
        public ?int $userId = null,
    ) {
        if ($quantity === 0) {
            throw new \InvalidArgumentException('Quantity must not be zero');
        }
    }
}
