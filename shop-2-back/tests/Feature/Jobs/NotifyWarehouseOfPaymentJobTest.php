<?php

namespace Tests\Feature\Jobs;

use App\Events\OrderPaid;
use App\Listeners\DispatchWarehouseNotification;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NotifyWarehouseOfPaymentJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_fires_order_paid_event_when_order_is_marked_as_paid(): void
    {
        Event::fake([OrderPaid::class]);

        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id'        => $user->id,
            'payment_status' => 'pending',
            'reservation_id' => 'reservation-123',
        ]);

        $this->actingAs($user)
             ->postJson("/api/orders/{$order->id}/pay")
             ->assertStatus(202);

        Event::assertDispatched(OrderPaid::class, function (OrderPaid $event) use ($order) {
            return $event->domainEvent->orderId === $order->id;
        });

        Event::assertListening(OrderPaid::class, DispatchWarehouseNotification::class);
    }

    public function test_returns_422_when_order_is_already_paid(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id'        => $user->id,
            'payment_status' => 'paid',
        ]);

        $this->actingAs($user)
             ->postJson("/api/orders/{$order->id}/pay")
             ->assertStatus(422);
    }
}
