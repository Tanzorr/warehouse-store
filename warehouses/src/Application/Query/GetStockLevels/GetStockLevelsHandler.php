<?php

namespace App\Application\Query\GetStockLevels;

use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class GetStockLevelsHandler
{
    public function __construct(
        private Connection $connection,
    ) {}

    /** @return StockLevelItem[] */
    public function __invoke(GetStockLevelsQuery $query): array
    {
        $sql = '
            SELECT
                p.id      AS product_id,
                p.name    AS product_name,
                p.sku     AS sku,
                c.name    AS category_name,
                w.id      AS warehouse_id,
                w.name    AS warehouse_name,
                sa.amount AS total_stock,
                COALESCE((
                    SELECT SUM(pri.amount)
                    FROM product_reservation_item pri
                    JOIN product_reservation pr ON pr.id = pri.product_reservation_id
                    WHERE pri.product_id = p.id AND pr.status = :pending_status
                ), 0) AS reserved_stock
            FROM stock_availability sa
            JOIN product p   ON p.id = sa.product_id
            JOIN warehouse w ON w.id = sa.warehouse_id
            JOIN category c  ON c.id = p.category_id
            WHERE 1 = 1
        ';

        $params = ['pending_status' => 'pending'];

        if ($query->warehouseId !== null) {
            $sql .= ' AND w.id = :warehouse_id';
            $params['warehouse_id'] = $query->warehouseId;
        }

        if ($query->categoryId !== null) {
            $sql .= ' AND c.id = :category_id';
            $params['category_id'] = $query->categoryId;
        }

        if ($query->search !== null) {
            $sql .= ' AND (p.name LIKE :search OR p.sku LIKE :search)';
            $params['search'] = '%'.$query->search.'%';
        }

        $sql .= ' ORDER BY w.name, p.name';

        $rows = $this->connection->fetchAllAssociative($sql, $params);

        $items = array_map(function (array $row) use ($query): StockLevelItem {
            $available = (int) $row['total_stock'] - (int) $row['reserved_stock'];

            return new StockLevelItem(
                productId: (int) $row['product_id'],
                productName: $row['product_name'],
                sku: $row['sku'],
                categoryName: $row['category_name'],
                warehouseId: (int) $row['warehouse_id'],
                warehouseName: $row['warehouse_name'],
                totalStock: (int) $row['total_stock'],
                reservedStock: (int) $row['reserved_stock'],
                availableStock: $available,
                isLowStock: $available <= $query->lowStockThreshold,
            );
        }, $rows);

        if ($query->lowStockOnly) {
            $items = array_filter($items, fn (StockLevelItem $item) => $item->isLowStock);
        }

        return array_values($items);
    }
}
