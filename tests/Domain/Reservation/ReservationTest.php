<?php

namespace App\Tests\Domain\Reservation;

use App\Domain\Reservation\ReservationItem;
use App\Domain\Reservation\Events\ReservationCommitted;
use App\Domain\Reservation\Reservation;
use App\Domain\Reservation\ReservationStatus;
use App\Domain\Reservation\ValueObjects\Quantity;
use App\Domain\Reservation\ValueObjects\ReservationId;
use PHPUnit\Framework\TestCase;

final class ReservationTest extends TestCase
{
    private function makeReservation():Reservation
    {
        return Reservation::place(
            new ReservationId(1),
            [new ReservationItem(productId: 1, quantity: new Quantity(2) )],
        );
    }

    public function test_place_create_pending_reservation(): void
    {
        $reservation = $this->makeReservation();

        $this->assertSame(ReservationStatus::Pending, $reservation->status());
        $this->assertCount(1, $reservation->items());
    }

    public function test_place_without_items_throws():void
    {
        $this->expectException(\DomainException::class);

        Reservation::place(new ReservationId(1), []);
    }

    public function test_commit_transitions_to_committed_to_commited_and_records_event():void
    {
         $reservation = $this->makeReservation();

         $reservation->commit();

         $this->assertSame(ReservationStatus::Committed, $reservation->status());

         $events = $reservation->pullEvents();
         $this->assertCount(1, $events);
         $this->assertInstanceOf(ReservationCommitted::class, $events[0]);
    }

    public function test_commit_twice_throws():void
    {
        $reservation = $this->makeReservation();
        $reservation->commit();

        $this->expectException(\DomainException::class);

        $reservation->commit();
    }

    public function test_pull_events_clears_the_buffer(): void
    {
        $reservation = $this->makeReservation();
        $reservation->commit();

        $reservation->pullEvents();
        $this->assertCount(0, $reservation->pullEvents());
    }



}
