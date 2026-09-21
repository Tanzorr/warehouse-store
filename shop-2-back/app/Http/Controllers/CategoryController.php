<?php

namespace App\Http\Controllers;

use App\Bus\CommandBusInterface;
use App\Bus\QueryBusInterface;
use App\Commands\Category\CreateCategoryCommand;
use App\Commands\Category\DeleteCategoryCommand;
use App\Commands\Category\UpdateCategoryCommand;
use App\Domain\Identity\ValueObjects\CategoryId;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Queries\Category\GetCategoriesQuery;
use App\Queries\Category\GetCategoryByIdQuery;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->queryBus->ask(new GetCategoriesQuery(request('search'))));
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $data = $request->validated();

        $this->commandBus->dispatch(new CreateCategoryCommand(
            name: $data['name'],
            description: $data['description'] ?? null,
        ));

        return response()->json(['message' => 'Category created successfully'], 201);
    }

    public function show(Category $category): JsonResponse
    {
        return response()->json($this->queryBus->ask(new GetCategoryByIdQuery($category->id)));
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $data = $request->validated();

        try {
            $this->commandBus->dispatch(new UpdateCategoryCommand(
                categoryId: new CategoryId($category->id),
                name: $data['name'],
                description: $data['description'] ?? null,
            ));
        } catch (\InvalidArgumentException|\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Category updated successfully']);
    }

    public function destroy(Category $category): JsonResponse
    {
        $deleted = $this->commandBus->dispatch(new DeleteCategoryCommand(new CategoryId($category->id)));

        return response()->json(null, $deleted ? 200 : 404);
    }
}
