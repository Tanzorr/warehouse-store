<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Reservation\Reservation;
use App\Domain\Reservation\ReservationItem;
use App\Domain\Reservation\ReservationStatus;
use App\Domain\Reservation\ValueObjects\Quantity;
use App\Domain\Reservation\ValueObjects\ReservationId;
use App\Entity\ProductReservation;
use App\Entity\ProductReservationItem;

final class ReservationMapper
{
    public function toDomain(ProductReservation $entity): Reservation
    {
        $items = array_map(fn(ProductReservationItem $item) => new ReservationItem(
            productId: $item->getProduct()->getId(),
            quantity: new Quantity($item->getAmount()),
        ),
            $entity->getItems()->toArray()
        );

        return Reservation::reconstitute(
            new ReservationId($entity->getId()),
            ReservationStatus::from($entity->getStatus()),
            $items,
        );
    }

    public function applyToEntity(Reservation $reservation, ProductReservation $entity): void
    {
        $entity->setStatus($reservation->status()->value);
    }
}
