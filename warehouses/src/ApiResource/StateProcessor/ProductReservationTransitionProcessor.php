<?php

namespace App\ApiResource\StateProcessor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Repository\ProductReservationRepository;
use InvalidArgumentException;
use Symfony\Component\Workflow\Registry;

final class ProductReservationTransitionProcessor implements ProcessorInterface
{
    public const WORKFLOW_NAME = 'product_reservation_workflow';

    public function __construct(
        private readonly Registry $workflowRegistry,
        private readonly ProductReservationRepository $productReservationRepository,
    )
    {

    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $payload = $data;
        $productReservation = $context['data'];
        $transition = $payload->transition ?? null;

        if (empty($transition)) {
            throw new InvalidArgumentException('Product reservation transition is not provided');
        }

        $workflow = $this->workflowRegistry->get($productReservation, self::WORKFLOW_NAME);

        if (!$workflow->can($productReservation, $transition)) {
            throw new InvalidArgumentException('workflow->can($productReservation, $transition) is false');
            //throw new LogicException(sprintf('Cannot apply transition "%s" for product reservation with current status "%s".', $transition, $workflow->getMarking($productReservation)->getPlaces()));
        }

        $workflow->apply($productReservation, $transition);
        $productReservation->setStatus($transition); // Ensure status is updated



        $this->productReservationRepository->save($productReservation);
        return $productReservation;
    }
}
