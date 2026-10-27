<?php

namespace App\QueryHandlers\Category;

use App\Models\Category;
use App\Queries\Category\GetCategoryByIdQuery;

class GetCategoryByIdHandler
{
    public function handle(GetCategoryByIdQuery $query): Category
    {
        return Category::findOrFail($query->categoryId);
    }
}
