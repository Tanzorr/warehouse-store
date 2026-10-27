<?php

namespace App\Controller;

use App\Application\Query\GetInventoryTransactions\GetInventoryTransactionsQuery;
use App\Application\Query\GetReservations\GetReservationsQuery;
use App\Application\Query\GetStockLevels\GetStockLevelsQuery;
use App\Application\Query\GetWarehouseSummary\GetWarehouseSummaryQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/warehouse')]
class WarehouseQueryController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $bus,
    ) {}

    #[Route('/stock', name: 'warehouse_stock_levels', methods: ['GET'])]
    public function getStockLevels(Request $request): JsonResponse
    {
        $envelope = $this->bus->dispatch(new GetStockLevelsQuery(
            warehouseId: $request->query->getInt('warehouse_id') ?: null,
            categoryId: $request->query->getInt('category_id') ?: null,
            search: $request->query->get('search'),
            lowStockOnly: $request->query->getBoolean('low_stock_only'),
            lowStockThreshold: $request->query->getInt('threshold', 5),
        ));

        return $this->json($envelope->last(HandledStamp::class)->getResult());
    }

    #[Route('/reservations', name: 'warehouse_reservations', methods: ['GET'])]
    public function getReservations(Request $request): JsonResponse
    {
        $envelope = $this->bus->dispatch(new GetReservationsQuery(
            status: $request->query->get('status'),
            productId: $request->query->getInt('product_id') ?: null,
            page: $request->query->getInt('page', 1),
            perPage: $request->query->getInt('per_page', 20),
        ));

        return $this->json($envelope->last(HandledStamp::class)->getResult());
    }

    #[Route('/stock/transactions', name: 'warehouse_stock_transactions', methods: ['GET'])]
    public function getTransactions(Request $request): JsonResponse
    {
        $envelope = $this->bus->dispatch(new GetInventoryTransactionsQuery(
            productId: $request->query->getInt('product_id') ?: null,
            warehouseId: $request->query->getInt('warehouse_id') ?: null,
            page: $request->query->getInt('page', 1),
            perPage: $request->query->getInt('per_page', 50),
        ));

        return $this->json($envelope->last(HandledStamp::class)->getResult());
    }

    #[Route('/warehouses/{id}/summary', name: 'warehouse_summary', methods: ['GET'])]
    public function getWarehouseSummary(int $id): JsonResponse
    {
        $envelope = $this->bus->dispatch(new GetWarehouseSummaryQuery($id));

        return $this->json($envelope->last(HandledStamp::class)->getResult());
    }
}
