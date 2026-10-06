<?php

namespace Database\Factories;

use Fam\Currencies\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Currency>
 */
class CurrencyFactory extends Factory
{
    protected $model = Currency::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $code = fake()->unique()->randomElement(array_keys(config('money.currencies')));

        return [
            'code' => $code,
            'name' => config("money.currencies.{$code}.name"),
        ];
    }

    public function code(string $code): static
    {
        return $this->state(fn (array $attributes): array => [
            'code' => $code,
            'name' => config("money.currencies.{$code}.name", $code),
        ]);
    }
}
