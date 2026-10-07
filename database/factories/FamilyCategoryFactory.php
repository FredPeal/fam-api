<?php

namespace Database\Factories;

use Fam\Categories\Models\Category;
use Fam\Categories\Models\FamilyCategory;
use Fam\Families\Models\Family;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FamilyCategory>
 */
class FamilyCategoryFactory extends Factory
{
    protected $model = FamilyCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'families_id' => Family::factory(),
            'categories_id' => Category::factory(),
        ];
    }

    public function removed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'deleted_at' => now(),
        ]);
    }
}
