<?php

namespace App\Application\Query\GetInventoryTransactions;

use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class GetInventoryTransactionsHandler
{
    public function __construct(
        private Connection $connection,
    ) {}

    /** @return array{items: TransactionItem[], total: int} */
    public function __invoke(GetInventoryTransactionsQuery $query): array
    {
        $where = '1 = 1';
        $params = [];

        if ($query->productId !== null) {
            $where .= ' AND it.product_id = :product_id';
            $params['product_id'] = $query->productId;
        }

        if ($query->warehouseId !== null) {
            $where .= ' AND it.warehouse_id = :warehouse_id';
            $params['warehouse_id'] = $query->warehouseId;
        }

        $total = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM inventory_transaction it WHERE {$where}",
            $params
        );

        $sql = "
            SELECT
                it.id,
                it.product_id,
                p.name    AS product_name,
                it.warehouse_id,
                w.name    AS warehouse_name,
                it.quantity,
                it.comment,
                it.entity_type,
                it.user_id,
                it.created_at
            FROM inventory_transaction it
            JOIN product p   ON p.id = it.product_id
            JOIN warehouse w ON w.id = it.warehouse_id
            WHERE {$where}
            ORDER BY it.created_at DESC, it.id DESC
            LIMIT :limit OFFSET :offset
        ";

        $params['limit'] = $query->perPage;
        $params['offset'] = ($query->page - 1) * $query->perPage;

        $rows = $this->connection->fetchAllAssociative($sql, $params, [
            'limit' => \Doctrine\DBAL\ParameterType::INTEGER,
            'offset' => \Doctrine\DBAL\ParameterType::INTEGER,
        ]);

        $items = array_map(fn (array $row) => new TransactionItem(
            id: (int) $row['id'],
            productId: (int) $row['product_id'],
            productName: $row['product_name'],
            warehouseId: (int) $row['warehouse_id'],
            warehouseName: $row['warehouse_name'],
            quantity: (int) $row['quantity'],
            comment: $row['comment'],
            entityType: $row['entity_type'],
            userId: (int) $row['user_id'],
            createdAt: $row['created_at'],
        ), $rows);

        return ['items' => $items, 'total' => $total];
    }
}
