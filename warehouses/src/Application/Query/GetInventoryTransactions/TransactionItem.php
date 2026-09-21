<?php

namespace App\Application\Query\GetInventoryTransactions;

readonly class TransactionItem
{
    public function __construct(
        public int     $id,
        public int     $productId,
        public string  $productName,
        public int     $warehouseId,
        public string  $warehouseName,
        public int     $quantity,
        public ?string $comment,
        public string  $entityType,
        public int     $userId,
        public string  $createdAt,
    ) {}
}
