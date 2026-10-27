<?php

namespace App\Domain\Product\Events;

use App\Domain\Product\ValueObjects\ProductId;

readonly class ProductUpdated
{
    public function __construct(
        public ProductId $productId,
        public \DateTimeImmutable $occurredAt,
    ) {}
}
