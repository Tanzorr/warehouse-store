<?php

namespace App\Commands\Category;

use App\Domain\Identity\ValueObjects\CategoryId;

readonly class DeleteCategoryCommand
{
    public function __construct(
        public CategoryId $categoryId,
    ) {}
}
