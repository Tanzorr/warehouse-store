<?php

namespace App\Application\Query\GetWarehouseSummary;

use App\Repository\WarehouseRepository;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class GetWarehouseSummaryHandler
{
    public function __construct(
        private Connection $connection,
        private WarehouseRepository $warehouses,
    ) {}

    public function __invoke(GetWarehouseSummaryQuery $query): WarehouseSummary
    {
        $warehouse = $this->warehouses->getOrFailById($query->warehouseId);

        $stockRow = $this->connection->fetchAssociative(
            'SELECT COUNT(DISTINCT product_id) AS total_products, COALESCE(SUM(amount), 0) AS total_stock
             FROM stock_availability
             WHERE warehouse_id = :warehouse_id',
            ['warehouse_id' => $query->warehouseId]
        );

        $lowStockCount = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM (
                SELECT
                    sa.product_id,
                    sa.amount - COALESCE((
                        SELECT SUM(pri.amount)
                        FROM product_reservation_item pri
                        JOIN product_reservation pr ON pr.id = pri.product_reservation_id
                        WHERE pri.product_id = sa.product_id AND pr.status = :pending_status
                    ), 0) AS available
                FROM stock_availability sa
                WHERE sa.warehouse_id = :warehouse_id
            ) stock_with_available
            WHERE available <= :threshold',
            [
                'warehouse_id' => $query->warehouseId,
                'pending_status' => 'pending',
                'threshold' => $query->lowStockThreshold,
            ]
        );

        $activeReservations = (int) $this->connection->fetchOne(
            'SELECT COUNT(DISTINCT pr.id)
             FROM product_reservation pr
             JOIN product_reservation_item pri ON pri.product_reservation_id = pr.id
             JOIN stock_availability sa ON sa.product_id = pri.product_id
             WHERE pr.status = :pending_status AND sa.warehouse_id = :warehouse_id',
            ['pending_status' => 'pending', 'warehouse_id' => $query->warehouseId]
        );

        $todayTransactions = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM inventory_transaction
             WHERE warehouse_id = :warehouse_id AND created_at >= CURDATE()',
            ['warehouse_id' => $query->warehouseId]
        );

        return new WarehouseSummary(
            warehouseId: $query->warehouseId,
            warehouseName: $warehouse->getName(),
            totalProducts: (int) $stockRow['total_products'],
            totalStock: (int) $stockRow['total_stock'],
            lowStockCount: $lowStockCount,
            activeReservations: $activeReservations,
            todayTransactions: $todayTransactions,
        );
    }
}
