<?php

namespace App\Commands\Category;

use App\Domain\Identity\ValueObjects\CategoryId;

readonly class UpdateCategoryCommand
{
    public function __construct(
        public CategoryId $categoryId,
        public string $name,
        public ?string $description,
    ) {}
}
