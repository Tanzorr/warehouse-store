<?php

namespace App\Infrastructure\Persistence\Product;

use App\Domain\Identity\ValueObjects\CategoryId;
use App\Domain\Product\Product as DomainProduct;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Product\ValueObjects\Sku;
use App\Domain\Shared\ValueObjects\Money;
use App\Domain\Shared\ValueObjects\Quantity;
use App\Models\Product as EloquentProduct;

class ProductMapper
{
    public function toDomain(EloquentProduct $model): DomainProduct
    {
        return DomainProduct::reconstitute(
            id: new ProductId($model->id),
            name: $model->name,
            description: $model->description,
            purchasePrice: new Money((float) $model->purchase_price),
            salePrice: new Money((float) $model->sale_price),
            stock: new Quantity($model->stock),
            sku: new Sku($model->sku),
            categoryId: new CategoryId($model->category_id),
        );
    }

    /** @return array<string, mixed> */
    public function toPersistence(DomainProduct $product): array
    {
        return [
            'id' => $product->getId()->value,
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'purchase_price' => $product->getPurchasePrice()->amount,
            'sale_price' => $product->getSalePrice()->amount,
            'stock' => $product->getStock()->value,
            'sku' => $product->getSku()->value,
            'category_id' => $product->getCategoryId()->value,
        ];
    }
}
