<?php

namespace App\CommandHandlers\Product;

use App\Commands\Product\CreateProductCommand;
use App\Domain\Product\Product as ProductAggregate;
use App\Domain\Product\ProductRepository;
use App\Domain\Shared\DomainEventDispatcher;
use App\Models\Product;
use App\Models\Tag;

class CreateProductHandler
{
    public function __construct(
        private readonly ProductRepository $repository,
        private readonly DomainEventDispatcher $eventDispatcher,
    ) {}

    public function handle(CreateProductCommand $command): Product
    {
        if ($this->repository->findBySku($command->sku) !== null) {
            throw new \DomainException("Product with SKU {$command->sku} already exists");
        }

        $aggregate = ProductAggregate::create(
            id: $this->repository->nextId(),
            name: $command->name,
            description: $command->description,
            purchasePrice: $command->purchasePrice,
            salePrice: $command->salePrice,
            stock: $command->stock,
            sku: $command->sku,
            categoryId: $command->categoryId,
        );

        $this->repository->save($aggregate);
        $this->eventDispatcher->dispatch($aggregate);

        $product = Product::findOrFail($aggregate->getId()->value);

        if (! empty($command->tags)) {
            $product->tags()->sync($this->getOrCreateTags($command->tags));
        }

        return $product;
    }

    private function getOrCreateTags(array $tagNames): array
    {
        return collect($tagNames)->map(fn ($tagName) => Tag::firstOrCreate(['name' => $tagName])->id)->toArray();
    }
}
