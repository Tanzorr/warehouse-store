<?php

namespace App\Application\Command\CommitReservation;

use App\Domain\Reservation\ReservationStatus;
use App\Infrastructure\Persistence\ReservationMapper;
use App\Repository\ProductReservationRepository;
use App\Service\StockAvailabilityService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class CommitReservationHandler
{
    public function __construct(
        private ProductReservationRepository $reservations,
        private StockAvailabilityService     $stockService,
        private EntityManagerInterface       $entityManager,
        private ReservationMapper            $mapper,
        private EventDispatcherInterface     $events,
    ) {}

    public function __invoke(CommitReservationCommand $command): void
    {
        $entity = $this->reservations->find((int) $command->reservationId);

        if ($entity === null) {
            return; // зникла або не існувала — ідемпотентно
        }

        $reservation = $this->mapper->toDomain($entity);

        // Ідемпотентність — concern доставки повідомлення, не доменне правило.
        // Повторна доставка вже закоммічденої резервації = тихий no-op.
        if ($reservation->status() === ReservationStatus::Committed) {
            return;
        }

        $this->stockService->commitReservation($entity);

        $reservation->commit();                              // домен: охорона переходу + запис події
        $this->mapper->applyToEntity($reservation, $entity);
        $this->entityManager->flush();

        foreach ($reservation->pullEvents() as $event) {
            $this->events->dispatch($event);                 // ReservationCommitted полетів
        }
    }
}
