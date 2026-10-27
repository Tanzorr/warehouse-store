<?php

namespace App\Providers;

use App\Bus\CommandBus;
use App\Bus\CommandBusInterface;
use App\Bus\QueryBus;
use App\Bus\QueryBusInterface;
use App\Contracts\MediaServiceInterface;
use App\Domain\Order\OrderRepository;
use App\Domain\Product\ProductRepository;
use App\Events\OrderPaid;
use App\Infrastructure\Persistence\Order\EloquentOrderRepository;
use App\Infrastructure\Persistence\Product\EloquentProductRepository;
use App\Listeners\DispatchWarehouseNotification;
use App\Models\Order;
use App\Observers\OrderObserver;
use App\Services\MediaService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            MediaServiceInterface::class,
            MediaService::class
        );

        $this->app->singleton(CommandBusInterface::class, CommandBus::class);
        $this->app->singleton(QueryBusInterface::class, QueryBus::class);

        $this->app->bind(ProductRepository::class, EloquentProductRepository::class);
        $this->app->bind(OrderRepository::class, EloquentOrderRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Order::observe(OrderObserver::class);

        Event::listen(OrderPaid::class, DispatchWarehouseNotification::class);
    }
}
