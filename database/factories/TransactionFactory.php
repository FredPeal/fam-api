<?php

namespace Database\Factories;

use App\Models\User;
use Fam\Accounts\Models\Account;
use Fam\Transactions\Enums\TransactionType;
use Fam\Transactions\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    /**
     * Define the model's default state. The account belongs to the transaction user and the
     * signed amount follows the type, even when the test overrides either of them.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'account_id' => fn (array $attributes): int => Account::factory()->create(['user_id' => $attributes['user_id']])->id,
            'currency_id' => fn (array $attributes): int => Account::query()->findOrFail($attributes['account_id'])->currency_id,
            'source_id' => null,
            'merchant_id' => null,
            'category_id' => null,
            'type' => TransactionType::Expense->value,
            'amount' => fake()->randomFloat(2, 1, 1000),
            'amount_signed' => fn (array $attributes): float => TransactionType::from($attributes['type'])->signedAmount((float) $attributes['amount']),
            'exchange_rate' => 1,
            'occurred_at' => fake()->dateTimeBetween('-1 month'),
            'last_balance_amount' => null,
            'description' => fake()->sentence(3),
            'notes' => null,
        ];
    }

    /**
     * Put the transaction on the account, owned by the account user and in the account currency.
     */
    public function forAccount(Account $account): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $account->user_id,
            'account_id' => $account->id,
            'currency_id' => $account->currency_id,
        ]);
    }

    public function ofType(TransactionType $type): static
    {
        return $this->state(fn (array $attributes): array => ['type' => $type->value]);
    }
}
