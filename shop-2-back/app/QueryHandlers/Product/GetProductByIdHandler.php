<?php

namespace App\QueryHandlers\Product;

use App\Models\Product;
use App\Queries\Product\GetProductByIdQuery;

class GetProductByIdHandler
{
    public function handle(GetProductByIdQuery $query): Product
    {
        return Product::with('tags')->findOrFail($query->productId);
    }
}
