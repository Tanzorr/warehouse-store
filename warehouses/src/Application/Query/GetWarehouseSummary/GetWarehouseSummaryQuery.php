<?php

namespace App\Application\Query\GetWarehouseSummary;

readonly class GetWarehouseSummaryQuery
{
    public function __construct(
        public int $warehouseId,
        public int $lowStockThreshold = 5,
    ) {}
}
