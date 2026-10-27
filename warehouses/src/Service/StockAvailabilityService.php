<?php

namespace App\Service;

use App\Entity\Product;
use App\Entity\ProductReservation;
use App\Repository\StockAvailabilityRepository;

readonly class StockAvailabilityService
{
    public function __construct(
        private StockAvailabilityRepository $repository,
        private ProductReservationsService $reservationsService
    )
    {
    }

    public function checkAccessedProductsInStock(Product $product, int $amount): bool
    {
        if ($amount <= 0) {
            return false;
        }

        $reservedAmount = $this->reservationsService->getReservedProductsAmount($product);

        $stocks = $this->repository->findByProductInStocks($product->getId());
        return $this->getStocksAmount($stocks) > ($amount + $reservedAmount);
    }

    private function getStocksAmount(array $stocks): int
    {
        $stocksAmount = 0;
        foreach ($stocks as $stock) {
            $stocksAmount += $stock->getAmount();
        }
        return $stocksAmount;
    }

    public function commitReservation(ProductReservation $reservation): string
    {
        try {
            foreach ($reservation->getItems() as $item) {
                $product = $item->getProduct();
                $amount = $item->getAmount();
                $stocks = $this->repository->findByProductInStocks($product->getId());
                $this->updatesStocksProductsAmount($stocks, $amount);
            }
            return ProductReservation::STATUS_COMMITTED;
        }catch (\Exception $exception){
            return $exception->getMessage();
        }
    }

    private function updatesStocksProductsAmount($stocks, int $amount): void
    {
        foreach ($stocks as $stock) {
            if ($stock->getAmount() >= $amount) {
                $stock->setAmount($stock->getAmount() - $amount);
                $this->repository->save($stock);
                return;
            } else {
                $amount -= $stock->getAmount();
                $stock->setAmount(0);
                $this->repository->save($stock);
            }
        }
    }

    public function releaseReservation(ProductReservation $reservation): void
    {
        // Pending reservations are not yet deducted from stock (deduction happens
        // in commitReservation), so releasing one does not require restoring stock.
    }

    public function getAvailableStock(Product $product):int
    {    $stocks = $this->repository->findByProductInStocks($product->getId());
        $allAmountInStocks = $this->getStocksAmount($stocks);
        $reservedAmount = $this->reservationsService->getReservedProductsAmount($product);
        return $allAmountInStocks - $reservedAmount;
    }
}
