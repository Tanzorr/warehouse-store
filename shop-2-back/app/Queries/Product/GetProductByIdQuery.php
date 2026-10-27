<?php

namespace App\Queries\Product;

readonly class GetProductByIdQuery
{
    public function __construct(
        public int $productId,
    ) {}
}
