<?php

namespace App\Tests\Domain\Reservation;

use App\Domain\Reservation\ReservationStatus;
use PHPUnit\Framework\TestCase;

final class ReservationStatusTest extends TestCase
{

    public function test_pending_can_transition_to_committed_and_cancelled(): void
    {
        $this->assertTrue(ReservationStatus::Pending->canTransitionTo(ReservationStatus::Committed));
        $this->assertTrue(ReservationStatus::Pending->canTransitionTo(ReservationStatus::Canceled));
    }

    public function test_committed_is_terminal(): void
    {
        $this->assertFalse(ReservationStatus::Committed->canTransitionTo(ReservationStatus::Committed));
        $this->assertFalse(ReservationStatus::Committed->canTransitionTo(ReservationStatus::Canceled));
    }

    public function test_canceled_is_terminal(): void
    {
        $this->assertFalse(ReservationStatus::Canceled->canTransitionTo(ReservationStatus::Committed));
        $this->assertFalse(ReservationStatus::Canceled->canTransitionTo(ReservationStatus::Pending));
    }


}
