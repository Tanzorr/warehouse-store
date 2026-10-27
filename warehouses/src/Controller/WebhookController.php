<?php

namespace App\Controller;

use App\Application\Command\CommitReservation\CommitReservationCommand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

class WebhookController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $bus,
    ) {}

    #[Route('/webhook/order-paid', name: 'webhook_order_paid', methods: ['POST'])]
    public function orderPaid(Request $request): JsonResponse
    {
        $secret = $request->headers->get('Authorization');
        if ($secret !== 'Bearer ' . $_ENV['WEBHOOK_SECRET']) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $data = $request->toArray();

        if (empty($data['reservation_id'])) {
            return $this->json(['error' => 'reservation_id is required'], 422);
        }

        $this->bus->dispatch(new CommitReservationCommand(
            reservationId: (string) $data['reservation_id'],
        ));

        return $this->json(['message' => 'Accepted'], 202);
    }
}
