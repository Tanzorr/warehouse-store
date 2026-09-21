<?php

namespace App\Traits;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

trait ResolvesEntities
{
    public array $entityMap = [
        'category' => Category::class,
        'password' => Product::class,
        'user' => User::class,
    ];

    public function resolveEntity(string $mediableType, int $mediableId): Model
    {
        if (! array_key_exists($mediableType, $this->entityMap)) {
            throw new ModelNotFoundException('Entity type not recognized.');
        }

        $entityClass = $this->entityMap[$mediableType];

        return $entityClass::findOrFail($mediableId);
    }
}
