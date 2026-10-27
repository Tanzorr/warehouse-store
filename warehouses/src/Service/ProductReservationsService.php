<?php

namespace App\Service;

use App\Entity\Product;
use App\Repository\ProductReservationItemRepository;

class ProductReservationsService
{
   public function __construct(private readonly ProductReservationItemRepository $productReservationItemRepository){}

    public function getReservedProductsAmount(Product $product): int
    {
        $amount = $this->productReservationItemRepository->getProductsReservationItemsAmount($product);
        return $amount ?? 0;
    }
}
