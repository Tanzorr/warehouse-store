<?php

namespace App\QueryHandlers\Product;

use App\Models\Product;
use App\Queries\Product\GetProductsQuery;

class GetProductsHandler
{
    public function handle(GetProductsQuery $query): array
    {
        $paginator = Product::query()
            ->search($query->search ?? '')
            ->filterByCategory($query->categoryIds)
            ->filterByTags($query->tagIds)
            ->paginate($query->perPage);

        $links = collect($paginator->linkCollection())
            ->map(function ($link) {
                $label = strip_tags($link['label']);

                if (str_contains($label, 'Previous')) {
                    $link['label'] = 'Previous';
                } elseif (str_contains($label, 'Next')) {
                    $link['label'] = 'Next';
                }

                return $link;
            })
            ->values();

        return [
            'data' => $paginator->items(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'links' => $links,
        ];
    }
}
