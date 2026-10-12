<?php

namespace Database\Factories;

use App\Models\User;
use Fam\Sources\Models\Source;
use Fam\Sources\Models\SourceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Source>
 */
class SourceFactory extends Factory
{
    protected $model = Source::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'source_type_id' => SourceType::factory(),
            'name' => fake()->words(2, true),
            'icon' => null,
        ];
    }
}
