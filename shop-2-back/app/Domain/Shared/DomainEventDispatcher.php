<?php

namespace App\Domain\Shared;

use App\Domain\Order\OrderPaymentConfirmed;
use App\Events\OrderPaid;
use Illuminate\Contracts\Events\Dispatcher;

class DomainEventDispatcher
{
    public function __construct(
        private readonly Dispatcher $events,
    ) {}

    public function dispatch(AggregateRoot $aggregate): void
    {
        foreach ($aggregate->pullEvents() as $event) {
            $this->dispatchEvent($event);
        }
    }

    public function dispatchEvent(object $event): void
    {
        if ($event instanceof OrderPaymentConfirmed) {
            $this->events->dispatch(new OrderPaid($event));

            return;
        }

        $this->events->dispatch($event);
    }
}
