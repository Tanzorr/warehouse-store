<?php

namespace App\CommandHandlers\Category;

use App\Commands\Category\CreateCategoryCommand;
use App\Models\Category;

class CreateCategoryHandler
{
    public function handle(CreateCategoryCommand $command): Category
    {
        return Category::create([
            'name' => $command->name,
            'description' => $command->description,
        ]);
    }
}
