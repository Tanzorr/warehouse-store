<?php

namespace App\Queries\Product;

readonly class GetProductsQuery
{
    public function __construct(
        public ?string $search = null,
        public ?array $categoryIds = null,
        public array $tagIds = [],
        public int $perPage = 10,
    ) {}
}
