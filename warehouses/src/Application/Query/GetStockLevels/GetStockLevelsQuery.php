<?php

namespace App\Application\Query\GetStockLevels;

readonly class GetStockLevelsQuery
{
    public function __construct(
        public ?int    $warehouseId = null,
        public ?int    $categoryId = null,
        public ?string $search = null,
        public bool    $lowStockOnly = false,
        public int     $lowStockThreshold = 5,
    ) {}
}
