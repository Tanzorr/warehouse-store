<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Jobs\NotifyWarehouseOfPaymentJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class DispatchWarehouseNotification implements ShouldQueue
{
    public function handle(OrderPaid $event): void
    {
        NotifyWarehouseOfPaymentJob::dispatch($event->domainEvent);
    }
}
