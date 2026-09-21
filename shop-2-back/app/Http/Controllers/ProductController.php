<?php

namespace App\Http\Controllers;

use App\Bus\CommandBusInterface;
use App\Bus\QueryBusInterface;
use App\Commands\Product\CreateProductCommand;
use App\Commands\Product\DeleteProductCommand;
use App\Commands\Product\UpdateProductCommand;
use App\Domain\Identity\ValueObjects\CategoryId;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Product\ValueObjects\Sku;
use App\Domain\Shared\ValueObjects\Money;
use App\Domain\Shared\ValueObjects\Quantity;
use App\Http\Requests\ProductFilterRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Queries\Product\GetProductByIdQuery;
use App\Queries\Product\GetProductsQuery;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
    ) {}

    public function index(ProductFilterRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $categoryIds = !empty($filters['category_ids'])
            ? explode(',', $filters['category_ids'])
            : null;

        $result = $this->queryBus->ask(new GetProductsQuery(
            search: $filters['search'] ?? null,
            categoryIds: $categoryIds,
            tagIds: $filters['tags_ids'] ?? [],
            perPage: $filters['per_page'] ?? 10,
        ));

        return response()->json($result);
    }

    /**
     * @throws \Exception
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $product = $this->commandBus->dispatch(new CreateProductCommand(
                name: $data['name'],
                description: $data['description'] ?? null,
                purchasePrice: new Money((float) $data['purchase_price']),
                salePrice: new Money((float) $data['sale_price']),
                stock: new Quantity($data['stock']),
                sku: new Sku($data['sku']),
                categoryId: new CategoryId($data['category_id']),
                tags: $data['tags'] ?? [],
            ));
        } catch (\InvalidArgumentException|\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($product->load('tags'), 201);
    }

    public function show(Product $product): JsonResponse
    {
        return response()->json($this->queryBus->ask(new GetProductByIdQuery($product->id)));
    }

    /**
     * @throws \Exception
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $data = $request->validated();

        try {
            $updated = $this->commandBus->dispatch(new UpdateProductCommand(
                productId: new ProductId($product->id),
                name: $data['name'],
                description: $data['description'] ?? null,
                purchasePrice: new Money((float) $data['purchase_price']),
                salePrice: new Money((float) $data['sale_price']),
                stock: new Quantity($data['stock']),
                sku: new Sku($data['sku']),
                categoryId: new CategoryId($data['category_id']),
                tags: $data['tags'] ?? [],
            ));
        } catch (\InvalidArgumentException|\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($updated->load('tags'));
    }

    public function destroy(Product $product): JsonResponse
    {
        $deleted = $this->commandBus->dispatch(new DeleteProductCommand(new ProductId($product->id)));

        return response()->json(null, $deleted ? 200 : 404);
    }
}
