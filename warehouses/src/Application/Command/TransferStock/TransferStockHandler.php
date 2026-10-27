<?php

namespace App\Application\Command\TransferStock;

use App\Entity\InventoryTransaction;
use App\Entity\StockAvailability;
use App\Repository\ProductRepository;
use App\Repository\StockAvailabilityRepository;
use App\Repository\WarehouseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class TransferStockHandler
{
    public function __construct(
        private StockAvailabilityRepository $stockRepository,
        private ProductRepository           $productRepository,
        private WarehouseRepository         $warehouseRepository,
        private EntityManagerInterface      $entityManager,
    ) {}

    public function __invoke(TransferStockCommand $command): void
    {
        $product = $this->productRepository->getOrFailById($command->productId);
        $this->warehouseRepository->getOrFailById($command->fromWarehouseId);
        $toWarehouse = $this->warehouseRepository->getOrFailById($command->toWarehouseId);

        $fromStock = $this->stockRepository->findByProductAndWarehouse($command->productId, $command->fromWarehouseId);
        if ($fromStock === null || $fromStock->getAmount() < $command->quantity) {
            throw new \DomainException('Insufficient stock in source warehouse');
        }

        $toStock = $this->stockRepository->findByProductAndWarehouse($command->productId, $command->toWarehouseId);
        if ($toStock === null) {
            $toStock = (new StockAvailability())
                ->setProduct($product)
                ->setWarehouse($toWarehouse)
                ->setAmount(0);
        }

        $fromStock->setAmount($fromStock->getAmount() - $command->quantity);
        $toStock->setAmount($toStock->getAmount() + $command->quantity);

        $this->stockRepository->save($fromStock);
        $this->stockRepository->save($toStock);

        $now = new \DateTimeImmutable();
        $this->entityManager->persist(new InventoryTransaction(
            id: null,
            entityId: $command->productId,
            createdAt: $now,
            warehouseId: $command->fromWarehouseId,
            comment: $command->comment,
            productId: $command->productId,
            quantity: -$command->quantity,
            userId: $command->userId,
            entityType: 'stock_transfer',
        ));
        $this->entityManager->persist(new InventoryTransaction(
            id: null,
            entityId: $command->productId,
            createdAt: $now,
            warehouseId: $command->toWarehouseId,
            comment: $command->comment,
            productId: $command->productId,
            quantity: $command->quantity,
            userId: $command->userId,
            entityType: 'stock_transfer',
        ));
        $this->entityManager->flush();
    }
}
