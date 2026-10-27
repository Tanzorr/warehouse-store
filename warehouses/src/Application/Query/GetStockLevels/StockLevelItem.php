<?php

namespace App\Application\Query\GetStockLevels;

readonly class StockLevelItem
{
    public function __construct(
        public int    $productId,
        public string $productName,
        public string $sku,
        public string $categoryName,
        public int    $warehouseId,
        public string $warehouseName,
        public int    $totalStock,
        public int    $reservedStock,
        public int    $availableStock,
        public bool   $isLowStock,
    ) {}
}
