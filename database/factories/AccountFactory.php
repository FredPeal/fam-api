<?php

namespace Database\Factories;

use App\Models\User;
use Fam\Accounts\Enums\AccountTypeId;
use Fam\Accounts\Models\Account;
use Fam\Accounts\Models\AccountType;
use Fam\Currencies\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $initialBalance = fake()->randomFloat(2, 0, 10000);

        return [
            'user_id' => User::factory(),
            'account_type_id' => fn (): int => AccountType::fromEnum(fake()->randomElement(AccountTypeId::cases()))->id,
            'currency_id' => Currency::factory(),
            'name' => fake()->words(2, true).' account',
            'description' => fake()->sentence(),
            'icon' => null,
            'initial_balance' => $initialBalance,
            'current_balance' => $initialBalance,
        ];
    }

    public function ofType(AccountTypeId $accountTypeId): static
    {
        return $this->state(fn (array $attributes): array => [
            'account_type_id' => AccountType::fromEnum($accountTypeId)->id,
        ]);
    }
}
