<?php

namespace App\Bus;

use App\Queries\Category\GetCategoriesQuery;
use App\Queries\Category\GetCategoryByIdQuery;
use App\Queries\Order\GetOrderByIdQuery;
use App\Queries\Order\GetOrdersQuery;
use App\Queries\Product\GetProductByIdQuery;
use App\Queries\Product\GetProductsQuery;
use App\QueryHandlers\Category\GetCategoriesHandler;
use App\QueryHandlers\Category\GetCategoryByIdHandler;
use App\QueryHandlers\Order\GetOrderByIdHandler;
use App\QueryHandlers\Order\GetOrdersHandler;
use App\QueryHandlers\Product\GetProductByIdHandler;
use App\QueryHandlers\Product\GetProductsHandler;
use Illuminate\Contracts\Container\Container;

class QueryBus implements QueryBusInterface
{
    /** @var array<class-string, class-string> */
    private array $map = [
        GetProductsQuery::class => GetProductsHandler::class,
        GetProductByIdQuery::class => GetProductByIdHandler::class,

        GetOrdersQuery::class => GetOrdersHandler::class,
        GetOrderByIdQuery::class => GetOrderByIdHandler::class,

        GetCategoriesQuery::class => GetCategoriesHandler::class,
        GetCategoryByIdQuery::class => GetCategoryByIdHandler::class,
    ];

    public function __construct(private readonly Container $container) {}

    public function ask(object $query): mixed
    {
        $handlerClass = $this->map[$query::class]
            ?? throw new \RuntimeException('No handler registered for '.$query::class);

        $handler = $this->container->make($handlerClass);

        return $handler->handle($query);
    }
}
