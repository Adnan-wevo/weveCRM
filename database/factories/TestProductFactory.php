<?php

namespace Database\Factories;

use App\Models\TestProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TestProduct>
 */
class TestProductFactory extends Factory
{
    protected $model = TestProduct::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'price' => fake()->numberBetween(100, 99999),
            'stock' => fake()->numberBetween(0, 1000),
        ];
    }
}
