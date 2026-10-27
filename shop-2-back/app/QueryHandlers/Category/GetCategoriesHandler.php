<?php

namespace App\QueryHandlers\Category;

use App\Models\Category;
use App\Queries\Category\GetCategoriesQuery;
use Illuminate\Pagination\LengthAwarePaginator;

class GetCategoriesHandler
{
    public function handle(GetCategoriesQuery $query): LengthAwarePaginator
    {
        return Category::filterBySearch($query->search)
            ->orderBy('created_at', 'desc')
            ->paginate(50);
    }
}
