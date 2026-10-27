<?php

namespace App\CommandHandlers\Product;

use App\Commands\Product\UpdateProductCommand;
use App\Domain\Product\ProductRepository;
use App\Domain\Shared\DomainEventDispatcher;
use App\Models\Product;
use App\Models\Tag;

class UpdateProductHandler
{
    public function __construct(
        private readonly ProductRepository $repository,
        private readonly DomainEventDispatcher $eventDispatcher,
    ) {}

    public function handle(UpdateProductCommand $command): Product
    {
        $aggregate = $this->repository->findById($command->productId);

        $aggregate->update(
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
