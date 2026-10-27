<?php

namespace App\Commands\Category;

readonly class CreateCategoryCommand
{
    public function __construct(
        public string $name,
        public ?string $description,
    ) {}
}
