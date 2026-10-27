<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $purchasePrice = $this->faker->randomFloat(2, 10, 1000); // Закупівельна ціна

        return [
            'category_id' => Category::factory(),
            'name' => $this->faker->word(),
            'description' => $this->faker->sentence(),
            'purchase_price' => $purchasePrice,
            'sale_price' => $purchasePrice + $this->faker->randomFloat(2, 1, 100),
            'stock' => $this->faker->numberBetween(0, 100),
            'sku' => $this->faker->unique()->bothify('SKU-#####'),
            'created_at' => now(),
            'updated_at' => now(),
        ];

    }
}
