<?php

namespace App\Commands\Product;

use App\Domain\Identity\ValueObjects\CategoryId;
use App\Domain\Product\ValueObjects\Sku;
use App\Domain\Shared\ValueObjects\Money;
use App\Domain\Shared\ValueObjects\Quantity;

readonly class CreateProductCommand
{
    public function __construct(
        public string $name,
        public ?string $description,
        public Money $purchasePrice,
        public Money $salePrice,
        public Quantity $stock,
        public Sku $sku,
        public CategoryId $categoryId,
        public array $tags = [],
    ) {}
}
