<?php

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $product = Product::find(Product::pluck('id')->random());

        if (! $product) {
            throw new \Exception('No products available for creating orders.');
        } else {
            return [
                'product_id' => $product ? $product->id : null,
                'quantity' => $this->faker->numberBetween(1, 5),
                'price' => $product->sale_price,
                'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
                'updated_at' => now(),
            ];
        }
    }
}
