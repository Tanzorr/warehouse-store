<?php

namespace App\CommandHandlers\Category;

use App\Commands\Category\DeleteCategoryCommand;
use App\Models\Category;

class DeleteCategoryHandler
{
    public function handle(DeleteCategoryCommand $command): bool
    {
        $category = Category::findOrFail($command->categoryId->value);

        return (bool) $category->delete();
    }
}
