<?php

namespace App\Application\Query\GetWarehouseSummary;

readonly class WarehouseSummary
{
    public function __construct(
        public int    $warehouseId,
        public string $warehouseName,
        public int    $totalProducts,
        public int    $totalStock,
        public int    $lowStockCount,
        public int    $activeReservations,
        public int    $todayTransactions,
    ) {}
}
