<?php

namespace App\Commands\Product;

use App\Domain\Product\ValueObjects\ProductId;

readonly class DeleteProductCommand
{
    public function __construct(
        public ProductId $productId,
    ) {}
}
