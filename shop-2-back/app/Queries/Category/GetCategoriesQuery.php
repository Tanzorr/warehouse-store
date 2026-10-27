<?php

namespace App\Queries\Category;

readonly class GetCategoriesQuery
{
    public function __construct(
        public ?string $search = null,
    ) {}
}
