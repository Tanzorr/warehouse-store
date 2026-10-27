<?php

namespace Tests\Unit\Doubles;

use App\Domain\Identity\ValueObjects\CategoryId;
use App\Domain\Product\Product;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Product\ValueObjects\Sku;
use App\Domain\Shared\NotFoundException;
use App\Domain\Shared\ValueObjects\Money;
use App\Domain\Shared\ValueObjects\Quantity;
use Tests\Doubles\InMemoryProductRepository;
use Tests\TestCase;

class InMemoryProductRepositoryTest extends TestCase
{
    private function makeProduct(ProductId $id, string $sku): Product
    {
        return Product::create(
            id: $id,
            name: 'Test Product',
            description: null,
            purchasePrice: new Money(10),
            salePrice: new Money(20),
            stock: new Quantity(5),
            sku: new Sku($sku),
            categoryId: new CategoryId(1),
        );
    }

    public function test_save_and_find_by_id(): void
    {
        $repository = new InMemoryProductRepository;
        $product = $this->makeProduct(new ProductId(1), 'SKU-1');

        $repository->save($product);

        $this->assertSame($product, $repository->findById(new ProductId(1)));
    }

    public function test_find_by_id_throws_when_missing(): void
    {
        $repository = new InMemoryProductRepository;

        $this->expectException(NotFoundException::class);

        $repository->findById(new ProductId(99));
    }

    public function test_find_by_sku(): void
    {
        $repository = new InMemoryProductRepository;
        $product = $this->makeProduct(new ProductId(1), 'SKU-1');
        $repository->save($product);

        $this->assertSame($product, $repository->findBySku(new Sku('SKU-1')));
        $this->assertNull($repository->findBySku(new Sku('SKU-2')));
    }

    public function test_delete_removes_product(): void
    {
        $repository = new InMemoryProductRepository;
        $product = $this->makeProduct(new ProductId(1), 'SKU-1');
        $repository->save($product);

        $repository->delete(new ProductId(1));

        $this->expectException(NotFoundException::class);
        $repository->findById(new ProductId(1));
    }

    public function test_next_id_increments(): void
    {
        $repository = new InMemoryProductRepository;

        $this->assertSame(1, $repository->nextId()->value);
        $this->assertSame(2, $repository->nextId()->value);
    }
}
