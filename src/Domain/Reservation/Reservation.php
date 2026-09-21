<?php

namespace App\Domain\Reservation;

use App\Domain\Reservation\Events\ReservationCommitted;
use App\Domain\Reservation\ValueObjects\ReservationId;
use App\Domain\Shared\AggregateRoot;

final class Reservation extends AggregateRoot
{
    /** @param ReservationItem[] $items */
    private function __construct(
        private readonly ReservationId $id,
        private ReservationStatus      $status,
        private readonly array         $items,
    ) {}

    /**
     * Born new reservation.
     * @param ReservationItem[] $items
     */
    public static function place(ReservationId $id, array $items): self
    {
        if ($items === []) {
            throw new \DomainException('Reservation must have at least one item');
        }

        return new self($id, ReservationStatus::Pending, $items);
    }

    /**
     * Reconstitute from persistence (mapper). Data was already valid — no events.
     * @param ReservationItem[] $items
     */
    public static function reconstitute(ReservationId $id, ReservationStatus $status, array $items): self
    {
        return new self($id, $status, $items);
    }

    public function commit(): void
    {
        $this->transitionTo(ReservationStatus::Committed);
        $this->recordEvent(new ReservationCommitted($this->id, new \DateTimeImmutable));
    }

    private function transitionTo(ReservationStatus $next): void
    {
        if (! $this->status->canTransitionTo($next)) {
            throw new \DomainException(
                sprintf('Cannot transition from %s to %s', $this->status->value, $next->value)
            );
        }

        $this->status = $next;
    }

    public function id(): ReservationId
    {
        return $this->id;
    }

    public function status(): ReservationStatus
    {
        return $this->status;
    }

    /** @return ReservationItem[] */
    public function items(): array
    {
        return $this->items;
    }
}
