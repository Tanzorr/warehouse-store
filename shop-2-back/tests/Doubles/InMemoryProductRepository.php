<?php

namespace Tests\Doubles;

use App\Domain\Product\Product;
use App\Domain\Product\ProductRepository;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Product\ValueObjects\Sku;
use App\Domain\Shared\NotFoundException;

class InMemoryProductRepository implements ProductRepository
{
    /** @var array<int, Product> */
    private array $products = [];

    private int $nextId = 1;

    public function findById(ProductId $id): Product
    {
        return $this->products[$id->value]
            ?? throw NotFoundException::forId('Product', $id->value);
    }

    public function findBySku(Sku $sku): ?Product
    {
        foreach ($this->products as $product) {
            if ($product->getSku()->equals($sku)) {
                return $product;
            }
        }

        return null;
    }

    public function save(Product $product): void
    {
        $this->products[$product->getId()->value] = $product;
    }

    public function delete(ProductId $id): void
    {
        unset($this->products[$id->value]);
    }

    public function nextId(): ProductId
    {
        return new ProductId($this->nextId++);
    }
}
