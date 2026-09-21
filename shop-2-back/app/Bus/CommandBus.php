<?php

namespace App\Bus;

use App\CommandHandlers\Category\CreateCategoryHandler;
use App\CommandHandlers\Category\DeleteCategoryHandler;
use App\CommandHandlers\Category\UpdateCategoryHandler;
use App\CommandHandlers\Order\CreateOrderHandler;
use App\CommandHandlers\Order\DeleteOrderHandler;
use App\CommandHandlers\Order\PayOrderHandler;
use App\CommandHandlers\Order\UpdateOrderHandler;
use App\CommandHandlers\Product\CreateProductHandler;
use App\CommandHandlers\Product\DeleteProductHandler;
use App\CommandHandlers\Product\UpdateProductHandler;
use App\Commands\Category\CreateCategoryCommand;
use App\Commands\Category\DeleteCategoryCommand;
use App\Commands\Category\UpdateCategoryCommand;
use App\Commands\Order\CreateOrderCommand;
use App\Commands\Order\DeleteOrderCommand;
use App\Commands\Order\PayOrderCommand;
use App\Commands\Order\UpdateOrderCommand;
use App\Commands\Product\CreateProductCommand;
use App\Commands\Product\DeleteProductCommand;
use App\Commands\Product\UpdateProductCommand;
use Illuminate\Contracts\Container\Container;

class CommandBus implements CommandBusInterface
{
    /** @var array<class-string, class-string> */
    private array $map = [
        CreateProductCommand::class => CreateProductHandler::class,
        UpdateProductCommand::class => UpdateProductHandler::class,
        DeleteProductCommand::class => DeleteProductHandler::class,

        CreateOrderCommand::class => CreateOrderHandler::class,
        UpdateOrderCommand::class => UpdateOrderHandler::class,
        DeleteOrderCommand::class => DeleteOrderHandler::class,
        PayOrderCommand::class => PayOrderHandler::class,

        CreateCategoryCommand::class => CreateCategoryHandler::class,
        UpdateCategoryCommand::class => UpdateCategoryHandler::class,
        DeleteCategoryCommand::class => DeleteCategoryHandler::class,
    ];

    public function __construct(private readonly Container $container) {}

    public function dispatch(object $command): mixed
    {
        $handlerClass = $this->map[$command::class]
            ?? throw new \RuntimeException('No handler registered for '.$command::class);

        $handler = $this->container->make($handlerClass);

        return $handler->handle($command);
    }
}
