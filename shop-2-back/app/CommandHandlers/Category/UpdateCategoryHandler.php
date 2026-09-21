<?php

namespace App\CommandHandlers\Category;

use App\Commands\Category\UpdateCategoryCommand;
use App\Models\Category;

class UpdateCategoryHandler
{
    public function handle(UpdateCategoryCommand $command): Category
    {
        $category = Category::findOrFail($command->categoryId->value);

        $category->update([
            'name' => $command->name,
            'description' => $command->description,
        ]);

        return $category;
    }
}
