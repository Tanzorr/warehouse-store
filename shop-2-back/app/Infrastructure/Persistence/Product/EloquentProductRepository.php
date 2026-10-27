<?php

namespace App\Infrastructure\Persistence\Product;

use App\Domain\Product\Product as DomainProduct;
use App\Domain\Product\ProductRepository;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Product\ValueObjects\Sku;
use App\Domain\Shared\NotFoundException;
use App\Models\Product as EloquentProduct;

class EloquentProductRepository implements ProductRepository
{
    public function __construct(
        private readonly ProductMapper $mapper,
    ) {}

    public function findById(ProductId $id): DomainProduct
    {
        $model = EloquentProduct::find($id->value);

        if ($model === null) {
            throw NotFoundException::forId('Product', $id->value);
        }

        return $this->mapper->toDomain($model);
    }

    public function findBySku(Sku $sku): ?DomainProduct
    {
        $model = EloquentProduct::where('sku', $sku->value)->first();

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function save(DomainProduct $product): void
    {
        EloquentProduct::updateOrCreate(
            ['id' => $product->getId()->value],
            $this->mapper->toPersistence($product),
        );
    }

    public function delete(ProductId $id): void
    {
        EloquentProduct::destroy($id->value);
    }

    public function nextId(): ProductId
    {
        return new ProductId((int) (EloquentProduct::max('id') ?? 0) + 1);
    }
}
