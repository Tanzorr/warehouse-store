<?php

namespace App\Domain\Product\Events;

use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Product\ValueObjects\Sku;
use App\Domain\Shared\ValueObjects\Money;

readonly class ProductCreated
{
    public function __construct(
        public ProductId $productId,
        public Sku $sku,
        public string $name,
        public Money $salePrice,
        public \DateTimeImmutable $occurredAt,
    ) {}
}
