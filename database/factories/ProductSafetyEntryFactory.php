<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductSafetyEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductSafetyEntry>
 */
class ProductSafetyEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'safety_text' => fake()->optional()->sentence(),
            'warning_text' => fake()->optional()->sentence(),
            'age_grading' => fake()->optional()->word(),
            'material_information' => fake()->optional()->sentence(),
            'usage_restrictions' => fake()->optional()->sentence(),
            'safety_instructions' => fake()->optional()->sentence(),
            'additional_notes' => fake()->optional()->sentence(),
        ];
    }
}
