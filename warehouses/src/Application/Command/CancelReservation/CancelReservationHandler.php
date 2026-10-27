<?php

namespace App\Application\Command\CancelReservation;

use App\Entity\ProductReservation;
use App\Repository\ProductReservationRepository;
use App\Service\StockAvailabilityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class CancelReservationHandler
{
    public function __construct(
        private ProductReservationRepository $reservations,
        private StockAvailabilityService     $stockService,
        private EntityManagerInterface       $entityManager,
    ) {}

    public function __invoke(CancelReservationCommand $command): void
    {
        $reservation = $this->reservations->find((int) $command->reservationId);
        if ($reservation === null) {
            throw new \DomainException('Reservation not found');
        }

        if ($reservation->getStatus() !== ProductReservation::STATUS_PENDING) {
            throw new \DomainException('Only pending reservations can be cancelled');
        }

        $this->stockService->releaseReservation($reservation);

        $reservation->setStatus(ProductReservation::STATUS_CANCELED);
        $reservation->setReleasedAt(new \DateTimeImmutable());
        if ($command->comment !== null) {
            $reservation->setComment($command->comment);
        }

        $this->entityManager->flush();
    }
}
