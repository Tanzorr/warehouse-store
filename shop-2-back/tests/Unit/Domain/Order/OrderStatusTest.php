<?php

namespace Tests\Unit\Domain\Order;

use App\Domain\Order\OrderStatus;
use Tests\TestCase;

class OrderStatusTest extends TestCase
{
    public function test_allows_transition_from_pending_to_paid(): void
    {
        $this->assertTrue(OrderStatus::Pending->canTransitionTo(OrderStatus::Paid));
    }

    public function test_allows_transition_from_pending_to_cancelled(): void
    {
        $this->assertTrue(OrderStatus::Pending->canTransitionTo(OrderStatus::Cancelled));
    }

    public function test_does_not_allow_transition_from_paid_to_cancelled(): void
    {
        $this->assertFalse(OrderStatus::Paid->canTransitionTo(OrderStatus::Cancelled));
    }

    public function test_does_not_allow_transition_from_paid_to_pending(): void
    {
        $this->assertFalse(OrderStatus::Paid->canTransitionTo(OrderStatus::Pending));
    }

    public function test_does_not_allow_transition_from_cancelled_to_paid(): void
    {
        $this->assertFalse(OrderStatus::Cancelled->canTransitionTo(OrderStatus::Paid));
    }
}
