<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $cost = fake()->randomFloat(2, 5, 200);

        return [
            'sku' => 'SKU-'.Str::upper(Str::random(6)),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'category_id' => Category::factory(),
            'cost_price' => $cost,
            'sale_price' => round($cost * fake()->randomFloat(2, 1.2, 2.5), 2),
            'stock' => 0,
            'min_stock' => fake()->numberBetween(0, 10),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
