<?php

namespace App\Controller;

use App\Application\Command\AdjustStock\AdjustStockCommand;
use App\Application\Command\CancelReservation\CancelReservationCommand;
use App\Application\Command\TransferStock\TransferStockCommand;
use App\Entity\StockAvailability;
use App\Security\JwtClaimUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class WarehouseCommandController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $bus,
    ) {}

    #[Route('/api/warehouse/reservations/{id}/cancel', name: 'warehouse_reservation_cancel', methods: ['POST'])]
    public function cancelReservation(string $id, Request $request): JsonResponse
    {
        $data = $request->toArray();

        try {
            $this->bus->dispatch(new CancelReservationCommand(
                reservationId: $id,
                comment: $data['comment'] ?? null,
            ));
        } catch (HandlerFailedException $e) {
            return $this->json(['error' => $this->unwrapMessage($e)], 422);
        }

        return $this->json(['message' => 'Reservation cancelled']);
    }

    #[Route('/api/warehouse/stock/adjust', name: 'warehouse_stock_adjust', methods: ['POST'])]
    public function adjustStock(Request $request): JsonResponse
    {
        $data = $request->toArray();

        foreach (['productId', 'warehouseId', 'quantity'] as $field) {
            if (!isset($data[$field])) {
                return $this->json(['error' => "{$field} is required"], 422);
            }
        }

        try {
            $envelope = $this->bus->dispatch(new AdjustStockCommand(
                productId: (int) $data['productId'],
                warehouseId: (int) $data['warehouseId'],
                quantity: (int) $data['quantity'],
                comment: $data['comment'] ?? null,
                userId: $this->currentUserId(),
            ));
        } catch (HandlerFailedException|\InvalidArgumentException $e) {
            return $this->json(['error' => $this->unwrapMessage($e)], 422);
        }

        /** @var StockAvailability $stock */
        $stock = $envelope->last(HandledStamp::class)->getResult();

        return $this->json([
            'productId' => $data['productId'],
            'warehouseId' => $data['warehouseId'],
            'amount' => $stock->getAmount(),
        ]);
    }

    #[Route('/api/warehouse/stock/transfer', name: 'warehouse_stock_transfer', methods: ['POST'])]
    public function transferStock(Request $request): JsonResponse
    {
        $data = $request->toArray();

        foreach (['productId', 'fromWarehouseId', 'toWarehouseId', 'quantity'] as $field) {
            if (!isset($data[$field])) {
                return $this->json(['error' => "{$field} is required"], 422);
            }
        }

        try {
            $this->bus->dispatch(new TransferStockCommand(
                productId: (int) $data['productId'],
                fromWarehouseId: (int) $data['fromWarehouseId'],
                toWarehouseId: (int) $data['toWarehouseId'],
                quantity: (int) $data['quantity'],
                comment: $data['comment'] ?? null,
                userId: $this->currentUserId(),
            ));
        } catch (HandlerFailedException|\InvalidArgumentException $e) {
            return $this->json(['error' => $this->unwrapMessage($e)], 422);
        }

        return $this->json(['message' => 'Stock transferred']);
    }

    /**
     * Laravel user id from the verified JWT, stamped onto inventory transactions.
     */
    private function currentUserId(): ?int
    {
        $user = $this->getUser();

        return $user instanceof JwtClaimUser ? $user->getId() : null;
    }

    private function unwrapMessage(HandlerFailedException|\InvalidArgumentException $e): string
    {
        if ($e instanceof HandlerFailedException) {
            $previous = $e->getPrevious();

            return $previous?->getMessage() ?? $e->getMessage();
        }

        return $e->getMessage();
    }
}
