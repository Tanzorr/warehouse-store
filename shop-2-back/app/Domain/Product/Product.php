<?php

namespace App\Domain\Product;

use App\Domain\Identity\ValueObjects\CategoryId;
use App\Domain\Product\Events\ProductCreated;
use App\Domain\Product\Events\ProductUpdated;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Product\ValueObjects\Sku;
use App\Domain\Shared\AggregateRoot;
use App\Domain\Shared\ValueObjects\Money;
use App\Domain\Shared\ValueObjects\Quantity;

class Product extends AggregateRoot
{
    private ProductId $id;

    private string $name;

    private ?string $description;

    private Money $purchasePrice;

    private Money $salePrice;

    private Quantity $stock;

    private Sku $sku;

    private CategoryId $categoryId;

    private function __construct() {}

    public static function create(
        ProductId $id,
        string $name,
        ?string $description,
        Money $purchasePrice,
        Money $salePrice,
        Quantity $stock,
        Sku $sku,
        CategoryId $categoryId,
    ): self {
        self::assertSalePriceAboveCost($salePrice, $purchasePrice);

        $product = new self;
        $product->id = $id;
        $product->name = $name;
        $product->description = $description;
        $product->purchasePrice = $purchasePrice;
        $product->salePrice = $salePrice;
        $product->stock = $stock;
        $product->sku = $sku;
        $product->categoryId = $categoryId;

        $product->recordEvent(new ProductCreated(
            productId: $id,
            sku: $sku,
            name: $name,
            salePrice: $salePrice,
            occurredAt: new \DateTimeImmutable,
        ));

        return $product;
    }

    public static function reconstitute(
        ProductId $id,
        string $name,
        ?string $description,
        Money $purchasePrice,
        Money $salePrice,
        Quantity $stock,
        Sku $sku,
        CategoryId $categoryId,
    ): self {
        $product = new self;
        $product->id = $id;
        $product->name = $name;
        $product->description = $description;
        $product->purchasePrice = $purchasePrice;
        $product->salePrice = $salePrice;
        $product->stock = $stock;
        $product->sku = $sku;
        $product->categoryId = $categoryId;

        return $product;
    }

    public function update(
        string $name,
        ?string $description,
        Money $purchasePrice,
        Money $salePrice,
        Quantity $stock,
        Sku $sku,
        CategoryId $categoryId,
    ): void {
        self::assertSalePriceAboveCost($salePrice, $purchasePrice);

        $this->name = $name;
        $this->description = $description;
        $this->purchasePrice = $purchasePrice;
        $this->salePrice = $salePrice;
        $this->stock = $stock;
        $this->sku = $sku;
        $this->categoryId = $categoryId;

        $this->recordEvent(new ProductUpdated(
            productId: $this->id,
            occurredAt: new \DateTimeImmutable,
        ));
    }

    private static function assertSalePriceAboveCost(Money $salePrice, Money $purchasePrice): void
    {
        if (! $salePrice->isGreaterThan($purchasePrice)) {
            throw new \DomainException('Sale price must be greater than purchase price');
        }
    }

    public function getId(): ProductId
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getPurchasePrice(): Money
    {
        return $this->purchasePrice;
    }

    public function getSalePrice(): Money
    {
        return $this->salePrice;
    }

    public function getStock(): Quantity
    {
        return $this->stock;
    }

    public function getSku(): Sku
    {
        return $this->sku;
    }

    public function getCategoryId(): CategoryId
    {
        return $this->categoryId;
    }
}
