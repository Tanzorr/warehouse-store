<?php

namespace App\Application\Command\AdjustStock;

use App\Entity\InventoryTransaction;
use App\Entity\StockAvailability;
use App\Repository\ProductRepository;
use App\Repository\StockAvailabilityRepository;
use App\Repository\WarehouseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class AdjustStockHandler
{
    public function __construct(
        private StockAvailabilityRepository $stockRepository,
        private ProductRepository           $productRepository,
        private WarehouseRepository         $warehouseRepository,
        private EntityManagerInterface      $entityManager,
    ) {}

    public function __invoke(AdjustStockCommand $command): StockAvailability
    {
        $product = $this->productRepository->getOrFailById($command->productId);
        $warehouse = $this->warehouseRepository->getOrFailById($command->warehouseId);

        $stock = $this->stockRepository->findByProductAndWarehouse($command->productId, $command->warehouseId);
        if ($stock === null) {
            $stock = (new StockAvailability())
                ->setProduct($product)
                ->setWarehouse($warehouse)
                ->setAmount(0);
        }

        $newAmount = $stock->getAmount() + $command->quantity;
        if ($newAmount < 0) {
            throw new \DomainException('Stock amount cannot become negative');
        }
        $stock->setAmount($newAmount);
        $this->stockRepository->save($stock);

        $this->entityManager->persist(new InventoryTransaction(
            id: null,
            entityId: $command->productId,
            createdAt: new \DateTimeImmutable(),
            warehouseId: $command->warehouseId,
            comment: $command->comment,
            productId: $command->productId,
            quantity: $command->quantity,
            userId: $command->userId,
            entityType: 'stock_adjustment',
        ));
        $this->entityManager->flush();

        return $stock;
    }
}
