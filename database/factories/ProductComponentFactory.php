<?php

namespace Database\Factories;

use App\Models\Distributor;
use App\Models\Product;
use App\Models\ProductComponent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductComponent>
 */
class ProductComponentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'distributor_id' => Distributor::factory(),
            'product_id' => fn (array $attributes): int => Product::factory()->create([
                'distributor_id' => $attributes['distributor_id'],
            ])->id,
            'name' => fake()->randomElement([
                'WiFi Module',
                'Bluetooth Module',
                'Battery',
                'USB Cable',
                'Charger / Power Supply',
            ]),
            'code' => fake()->optional()->slug(2),
            'detected_at' => fake()->optional()->dateTime(),
        ];
    }
}
