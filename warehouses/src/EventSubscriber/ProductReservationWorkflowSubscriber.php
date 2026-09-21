<?php

namespace App\EventSubscriber;

use App\Entity\ProductReservation;
use App\Service\StockAvailabilityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

readonly class ProductReservationWorkflowSubscriber implements EventSubscriberInterface
{

    public function __construct(
        private StockAvailabilityService $stockAvailabilityService,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.product_reservation_workflow.completed.committed' => 'onCommit',
            'workflow.product_reservation_workflow.completed.cancel' => 'onCancel',
        ];
    }

    public function onCommit(CompletedEvent $event): void
    {
        $reservation = $this->getReservation($event);

        $this->logger->info(sprintf('Reservation #%d has been committed. Deducting stock...', $reservation->getId()));
        $this->stockAvailabilityService->commitReservation($reservation);

        $this->entityManager->flush();

        $this->logger->info(sprintf('Stock deduction for reservation #%d completed.', $reservation->getId()));
    }

    public function onCancel(CompletedEvent $event): void
    {
        $reservation = $this->getReservation($event);

        $this->logger->info(sprintf('Reservation #%d has been cancelled. Restoring stock...', $reservation->getId()));
        $this->entityManager->flush();
    }

    private function getReservation(CompletedEvent $event): ProductReservation
    {
        $subject = $event->getSubject();
        if (!$subject instanceof ProductReservation) {
            throw new InvalidArgumentException('Expected ProductReservation, got ' . get_class($subject));
        }
        return $subject;
    }
}
