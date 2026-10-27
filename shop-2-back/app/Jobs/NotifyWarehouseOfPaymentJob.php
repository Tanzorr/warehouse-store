<?php

namespace App\Jobs;

use App\Domain\Order\OrderPaymentConfirmed;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotifyWarehouseOfPaymentJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;

    public function __construct(
        private readonly OrderPaymentConfirmed $event,
    ) {}

    /** Exponential backoff: 10s, 30s, 90s */
    public function backoff(): array
    {
        return [10, 30, 90];
    }

    public function handle(HttpFactory $http): void
    {
        $response = $http
            ->withToken(config('services.warehouses.secret'))
            ->post(config('services.warehouses.url') . '/webhook/order-paid', [
                'order_id'       => $this->event->orderId,
                'reservation_id' => $this->event->reservationExternalId,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException(
                "Warehouse webhook failed: HTTP {$response->status()}"
            );
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('NotifyWarehouseOfPaymentJob failed permanently', [
            'order_id' => $this->event->orderId,
            'error'    => $exception->getMessage(),
        ]);
    }
}
