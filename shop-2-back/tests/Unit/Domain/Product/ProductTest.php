<?php

namespace Tests\Unit\Domain\Product;

use App\Domain\Identity\ValueObjects\CategoryId;
use App\Domain\Product\Events\ProductCreated;
use App\Domain\Product\Events\ProductUpdated;
use App\Domain\Product\Product;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Product\ValueObjects\Sku;
use App\Domain\Shared\ValueObjects\Money;
use App\Domain\Shared\ValueObjects\Quantity;
use Tests\TestCase;

class ProductTest extends TestCase
{
    private function makeProduct(float $purchasePrice = 10.0, float $salePrice = 20.0): Product
    {
        return Product::create(
            id: new ProductId(1),
            name: 'Test Product',
            description: 'A product',
            purchasePrice: new Money($purchasePrice),
            salePrice: new Money($salePrice),
            stock: new Quantity(5),
            sku: new Sku('SKU-1'),
            categoryId: new CategoryId(1),
        );
    }

    public function test_create_records_product_created_event(): void
    {
        $product = $this->makeProduct();

        $events = $product->pullEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(ProductCreated::class, $events[0]);
    }

    public function test_create_throws_when_sale_price_not_greater_than_purchase_price(): void
    {
        $this->expectException(\DomainException::class);

        $this->makeProduct(purchasePrice: 20.0, salePrice: 20.0);
    }

    public function test_update_throws_when_sale_price_not_greater_than_purchase_price(): void
    {
        $product = $this->makeProduct();
        $product->pullEvents();

        $this->expectException(\DomainException::class);

        $product->update(
            name: 'Updated',
            description: null,
            purchasePrice: new Money(30.0),
            salePrice: new Money(20.0),
            stock: new Quantity(1),
            sku: new Sku('SKU-1'),
            categoryId: new CategoryId(1),
        );
    }

    public function test_update_records_product_updated_event(): void
    {
        $product = $this->makeProduct();
        $product->pullEvents();

        $product->update(
            name: 'Updated',
            description: null,
            purchasePrice: new Money(10.0),
            salePrice: new Money(25.0),
            stock: new Quantity(2),
            sku: new Sku('SKU-1'),
            categoryId: new CategoryId(1),
        );

        $events = $product->pullEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(ProductUpdated::class, $events[0]);
        $this->assertSame(25.0, $product->getSalePrice()->amount);
    }
}
