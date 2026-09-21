<?php

namespace App\Application\Query\GetInventoryTransactions;

readonly class GetInventoryTransactionsQuery
{
    public function __construct(
        public ?int $productId = null,
        public ?int $warehouseId = null,
        public int  $page = 1,
        public int  $perPage = 50,
    ) {}
}
