<?php

namespace App\Observers;

use App\Jobs\CancelUnpaidOrderJob;
use App\Models\Order;

class OrderObserver
{
    /**
     * Handle the Order "created" event.
     */

    public function created(Order $order): void
    {
        if ($order->status === 'pending') {
            CancelUnpaidOrderJob::dispatch($order)->delay(now()->addMinutes(5));
        }
    }
}
