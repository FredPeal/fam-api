<?php

namespace Database\Factories;

use Fam\Sources\Models\SourceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SourceType>
 */
class SourceTypeFactory extends Factory
{
    protected $model = SourceType::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->slug(2),
            'icon' => null,
        ];
    }
}
