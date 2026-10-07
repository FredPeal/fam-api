<?php

namespace Database\Factories;

use Fam\Categories\Models\Category;
use Fam\Merchants\Models\Merchant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Merchant>
 */
class MerchantFactory extends Factory
{
    protected $model = Merchant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'code' => fake()->unique()->slug(2),
            'name' => fake()->company(),
            'description' => fake()->sentence(),
        ];
    }
}
