<?php

namespace App\EventListener;

use App\Entity\ProductReservation;
use App\Service\StockAvailabilityService;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\Workflow\Registry;

class ProductReservationListener
{
    public const WORKFLOW_NAME = 'product_reservation_workflow';
    public function __construct(
        protected StockAvailabilityService $stockAvailabilityService,
        private readonly Registry          $workflowRegistry,
    ) {}

    public function preUpdate(ProductReservation $productReservation, PreUpdateEventArgs $args): void
    {

        if (!$args->hasChangedField('status')) {
            return;
        }

        $oldStatus = $args->getOldValue('status');
        $newStatus = $args->getNewValue('status');

        $workflow = $this->workflowRegistry->get($productReservation, self::WORKFLOW_NAME);


        if ($oldStatus === ProductReservation::STATUS_PENDING && $newStatus === ProductReservation::STATUS_COMMITTED) {
            if ($workflow->can($productReservation, 'commit')) {
                $workflow->apply($productReservation, 'commit');
            }
        }

        if ($oldStatus === ProductReservation::STATUS_PENDING && $newStatus === ProductReservation::STATUS_CANCELED) {
            if ($workflow->can($productReservation, 'cancel')) {
                $workflow->apply($productReservation, 'cancel');
            }
        }
    }
}
