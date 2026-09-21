<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => rand(1, 100),
            'status' => $this->faker->randomElement(['pending', 'processing', 'shipped', 'delivered', 'canceled']),
            'total_price' => 0,
            'payment_status' => $this->faker->randomElement(['pending', 'paid', 'failed', 'refunded']),
            'payment_method' => $this->faker->randomElement(['credit_card', 'paypal', 'bank_transfer', 'cash_on_delivery']),
            'shipping_address' => $this->faker->address,
            'billing_address' => $this->faker->address,
            'notes' => $this->faker->optional()->sentence,
            'created_at' => $this->faker->dateTimeBetween('-12 months', 'now'),
            'updated_at' => now(),
        ];
    }
}
