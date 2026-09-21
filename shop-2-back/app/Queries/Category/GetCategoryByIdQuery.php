<?php

namespace App\Queries\Category;

readonly class GetCategoryByIdQuery
{
    public function __construct(
        public int $categoryId,
    ) {}
}
