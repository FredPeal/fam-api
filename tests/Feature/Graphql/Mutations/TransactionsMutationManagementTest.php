<?php

namespace Tests\Feature\Graphql\Mutations;

use App\Models\User;
use Fam\Accounts\Models\Account;
use Fam\Categories\Models\Category;
use Fam\Currencies\Models\Currency;
use Fam\Merchants\Models\Merchant;
use Fam\Sources\Models\Source;
use Fam\Transactions\Enums\TransactionType;
use Fam\Transactions\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TransactionsMutationManagementTest extends TestCase
{
    use MakesGraphQLRequests;
    use RefreshDatabase;

    private function usdAccount(User $user, float $balance): Account
    {
        return Account::factory()
            ->for($user)
            ->for(Currency::factory()->code('USD'))
            ->create(['initial_balance' => $balance, 'current_balance' => $balance]);
    }

    public function test_create_expense_stores_the_transaction_with_its_relations_and_subtracts_it_from_the_account(): void
    {
        $user = User::factory()->create();
        $account = $this->usdAccount($user, 1000);
        $category = Category::factory()->for($user)->create();
        $merchant = Merchant::factory()->for($category)->create();
        $source = Source::factory()->for($user)->create();
        Sanctum::actingAs($user);

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation ($accountId: ID!, $categoryId: ID, $merchantId: ID, $sourceId: ID) {
                createTransaction(input: {
                    account_id: $accountId, type: EXPENSE, amount: 125.5, currency_code: "USD",
                    occurred_at: "2026-10-01 10:30:00", category_id: $categoryId, merchant_id: $merchantId,
                    source_id: $sourceId, description: "Groceries", notes: "Weekly"
                }) {
                    type amount amount_signed exchange_rate occurred_at last_balance_amount description notes
                    account { id current_balance } user { id } currency { code }
                    category { id } merchant { id } source { id }
                }
            }
        ', ['accountId' => $account->id, 'categoryId' => $category->id, 'merchantId' => $merchant->id, 'sourceId' => $source->id]);

        $response->assertGraphQLErrorFree();
        $response->assertJsonPath('data.createTransaction', [
            'type' => 'EXPENSE',
            'amount' => 125.5,
            'amount_signed' => -125.5,
            'exchange_rate' => 1,
            'occurred_at' => '2026-10-01 10:30:00',
            'last_balance_amount' => 1000,
            'description' => 'Groceries',
            'notes' => 'Weekly',
            'account' => ['id' => (string) $account->id, 'current_balance' => 874.5],
            'user' => ['id' => (string) $user->id],
            'currency' => ['code' => 'USD'],
            'category' => ['id' => (string) $category->id],
            'merchant' => ['id' => (string) $merchant->id],
            'source' => ['id' => (string) $source->id],
        ]);
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'account_id' => $account->id,
            'type' => 'expense',
            'amount' => 125.5,
            'amount_signed' => -125.5,
            'last_balance_amount' => 1000,
        ]);
        $this->assertDatabaseHas('accounts', ['id' => $account->id, 'current_balance' => 874.5]);
    }

    /**
     * @return array<string, array{string, string, int|float, int|float, int|float}>
     */
    public static function amountsByType(): array
    {
        return [
            'income adds the absolute amount' => ['INCOME', '-200', 200, 200, 1200],
            'expense subtracts the absolute amount' => ['EXPENSE', '200', 200, -200, 800],
            'negative transfer subtracts' => ['TRANSFER', '-300', 300, -300, 700],
            'positive transfer adds' => ['TRANSFER', '300', 300, 300, 1300],
            'negative adjustment subtracts' => ['ADJUSTMENT', '-0.5', 0.5, -0.5, 999.5],
            'positive adjustment adds' => ['ADJUSTMENT', '0.5', 0.5, 0.5, 1000.5],
        ];
    }

    #[DataProvider('amountsByType')]
    public function test_create_transaction_signs_the_amount_by_type_and_applies_it_to_the_balance(
        string $type,
        string $amount,
        int|float $expectedAmount,
        int|float $expectedSigned,
        int|float $expectedBalance,
    ): void {
        $user = User::factory()->create();
        $account = $this->usdAccount($user, 1000);
        Sanctum::actingAs($user);

        $response = $this->graphQL(/** @lang GraphQL */ "
            mutation (\$accountId: ID!) {
                createTransaction(input: {
                    account_id: \$accountId, type: {$type}, amount: {$amount}, currency_code: \"USD\", occurred_at: \"2026-10-01 00:00:00\"
                }) { amount amount_signed account { current_balance } }
            }
        ", ['accountId' => $account->id]);

        $response->assertGraphQLErrorFree();
        $response->assertJsonPath('data.createTransaction', [
            'amount' => $expectedAmount,
            'amount_signed' => $expectedSigned,
            'account' => ['current_balance' => $expectedBalance],
        ]);
    }

    public function test_create_transaction_in_another_currency_applies_the_exchange_rate_to_the_balance(): void
    {
        $user = User::factory()->create();
        $account = $this->usdAccount($user, 1000);
        Currency::factory()->code('DOP')->create();
        Sanctum::actingAs($user);

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation ($accountId: ID!) {
                createTransaction(input: {
                    account_id: $accountId, type: EXPENSE, amount: 600, currency_code: "DOP",
                    occurred_at: "2026-10-01 00:00:00", exchange_rate: 0.0165
                }) { amount amount_signed exchange_rate currency { code } account { current_balance } }
            }
        ', ['accountId' => $account->id]);

        $response->assertGraphQLErrorFree();
        $response->assertJsonPath('data.createTransaction', [
            'amount' => 600,
            'amount_signed' => -600,
            'exchange_rate' => 0.0165,
            'currency' => ['code' => 'DOP'],
            'account' => ['current_balance' => 990.1],
        ]);
    }

    public function test_create_transaction_with_zero_amount_returns_validation_error_and_creates_nothing(): void
    {
        $user = User::factory()->create();
        $account = $this->usdAccount($user, 1000);
        Sanctum::actingAs($user);

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation ($accountId: ID!) {
                createTransaction(input: {
                    account_id: $accountId, type: EXPENSE, amount: 0, currency_code: "USD", occurred_at: "2026-10-01 00:00:00"
                }) { id }
            }
        ', ['accountId' => $account->id]);

        $response->assertGraphQLValidationError('input.amount', 'The selected input.amount is invalid.');
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseHas('accounts', ['id' => $account->id, 'current_balance' => 1000]);
    }

    public function test_create_transaction_on_an_account_owned_by_another_user_returns_not_found_error_and_creates_nothing(): void
    {
        $otherAccount = Account::factory()->for(Currency::factory()->code('USD'))->create(['current_balance' => 500]);
        Sanctum::actingAs(User::factory()->create());

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation ($accountId: ID!) {
                createTransaction(input: {
                    account_id: $accountId, type: EXPENSE, amount: 10, currency_code: "USD", occurred_at: "2026-10-01 00:00:00"
                }) { id }
            }
        ', ['accountId' => $otherAccount->id]);

        $response->assertGraphQLErrorMessage('Account not found.');
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseHas('accounts', ['id' => $otherAccount->id, 'current_balance' => 500]);
    }

    public function test_create_transaction_with_a_category_owned_by_another_user_returns_not_found_error_and_creates_nothing(): void
    {
        $user = User::factory()->create();
        $account = $this->usdAccount($user, 1000);
        $otherCategory = Category::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation ($accountId: ID!, $categoryId: ID) {
                createTransaction(input: {
                    account_id: $accountId, type: EXPENSE, amount: 10, currency_code: "USD",
                    occurred_at: "2026-10-01 00:00:00", category_id: $categoryId
                }) { id }
            }
        ', ['accountId' => $account->id, 'categoryId' => $otherCategory->id]);

        $response->assertGraphQLErrorMessage('Category not found.');
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_update_transaction_changes_the_fields_and_replaces_its_effect_on_the_account_balance(): void
    {
        $user = User::factory()->create();
        $account = $this->usdAccount($user, 900);
        $transaction = Transaction::factory()
            ->forAccount($account)
            ->ofType(TransactionType::Expense)
            ->create(['amount' => 100, 'description' => 'Old']);
        Sanctum::actingAs($user);

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation ($id: ID!, $accountId: ID!) {
                updateTransaction(id: $id, input: {
                    account_id: $accountId, type: INCOME, amount: 50, currency_code: "USD",
                    occurred_at: "2026-10-02 08:00:00", description: "New"
                }) { type amount amount_signed occurred_at last_balance_amount description account { current_balance } }
            }
        ', ['id' => $transaction->id, 'accountId' => $account->id]);

        $response->assertGraphQLErrorFree();
        $response->assertJsonPath('data.updateTransaction', [
            'type' => 'INCOME',
            'amount' => 50,
            'amount_signed' => 50,
            'occurred_at' => '2026-10-02 08:00:00',
            'last_balance_amount' => 1000,
            'description' => 'New',
            'account' => ['current_balance' => 1050],
        ]);
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'type' => 'income',
            'amount' => 50,
            'amount_signed' => 50,
            'description' => 'New',
        ]);
        $this->assertDatabaseHas('accounts', ['id' => $account->id, 'current_balance' => 1050]);
    }

    public function test_update_transaction_to_another_account_moves_its_effect_between_both_balances(): void
    {
        $user = User::factory()->create();
        $previousAccount = $this->usdAccount($user, 900);
        $newAccount = Account::factory()->for($user)->for(Currency::factory()->code('EUR'))->create(['current_balance' => 200]);
        $transaction = Transaction::factory()
            ->forAccount($previousAccount)
            ->ofType(TransactionType::Expense)
            ->create(['amount' => 100]);
        Sanctum::actingAs($user);

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation ($id: ID!, $accountId: ID!) {
                updateTransaction(id: $id, input: {
                    account_id: $accountId, type: EXPENSE, amount: 100, currency_code: "USD", occurred_at: "2026-10-01 00:00:00"
                }) { last_balance_amount account { id current_balance } }
            }
        ', ['id' => $transaction->id, 'accountId' => $newAccount->id]);

        $response->assertGraphQLErrorFree();
        $response->assertJsonPath('data.updateTransaction', [
            'last_balance_amount' => 200,
            'account' => ['id' => (string) $newAccount->id, 'current_balance' => 100],
        ]);
        $this->assertDatabaseHas('accounts', ['id' => $previousAccount->id, 'current_balance' => 1000]);
        $this->assertDatabaseHas('accounts', ['id' => $newAccount->id, 'current_balance' => 100]);
    }

    public function test_update_transaction_owned_by_another_user_returns_not_found_error_and_changes_nothing(): void
    {
        $otherAccount = Account::factory()->for(Currency::factory()->code('EUR'))->create(['current_balance' => 500]);
        $otherTransaction = Transaction::factory()->forAccount($otherAccount)->create(['description' => 'Untouched']);
        $user = User::factory()->create();
        $account = $this->usdAccount($user, 1000);
        Sanctum::actingAs($user);

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation ($id: ID!, $accountId: ID!) {
                updateTransaction(id: $id, input: {
                    account_id: $accountId, type: EXPENSE, amount: 10, currency_code: "USD",
                    occurred_at: "2026-10-01 00:00:00", description: "Hijacked"
                }) { id }
            }
        ', ['id' => $otherTransaction->id, 'accountId' => $account->id]);

        $response->assertGraphQLErrorMessage('Transaction not found.');
        $this->assertDatabaseHas('transactions', ['id' => $otherTransaction->id, 'description' => 'Untouched']);
        $this->assertDatabaseHas('accounts', ['id' => $otherAccount->id, 'current_balance' => 500]);
    }

    public function test_delete_transaction_removes_it_and_reverts_its_effect_on_the_account_balance(): void
    {
        $user = User::factory()->create();
        $account = $this->usdAccount($user, 1250);
        $transaction = Transaction::factory()
            ->forAccount($account)
            ->ofType(TransactionType::Income)
            ->create(['amount' => 250]);
        Sanctum::actingAs($user);

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation ($id: ID!) { deleteTransaction(id: $id) }
        ', ['id' => $transaction->id]);

        $response->assertGraphQLErrorFree();
        $response->assertJsonPath('data.deleteTransaction', true);
        $this->assertModelMissing($transaction);
        $this->assertDatabaseHas('accounts', ['id' => $account->id, 'current_balance' => 1000]);
    }

    public function test_delete_transaction_owned_by_another_user_returns_not_found_error_and_keeps_it(): void
    {
        $otherTransaction = Transaction::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation ($id: ID!) { deleteTransaction(id: $id) }
        ', ['id' => $otherTransaction->id]);

        $response->assertGraphQLErrorMessage('Transaction not found.');
        $this->assertModelExists($otherTransaction);
    }
}
