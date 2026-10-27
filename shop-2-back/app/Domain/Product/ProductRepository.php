<?php

namespace App\Domain\Product;

use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Product\ValueObjects\Sku;

interface ProductRepository
{
    /**
     * @throws \App\Domain\Shared\NotFoundException
     */
    public function findById(ProductId $id): Product;

    public function findBySku(Sku $sku): ?Product;

    public function save(Product $product): void;

    public function delete(ProductId $id): void;

    public function nextId(): ProductId;
}
