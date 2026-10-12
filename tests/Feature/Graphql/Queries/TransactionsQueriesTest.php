<?php

namespace Tests\Feature\Graphql\Queries;

use App\Models\User;
use Fam\Accounts\Models\Account;
use Fam\Categories\Models\Category;
use Fam\Transactions\Enums\TransactionType;
use Fam\Transactions\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

class TransactionsQueriesTest extends TestCase
{
    use MakesGraphQLRequests;
    use RefreshDatabase;

    public function test_unauthenticated_request_returns_unauthenticated_error(): void
    {
        $response = $this->graphQL(/** @lang GraphQL */ '
            { transactions { data { id } } }
        ');

        $response->assertGraphQLErrorMessage('Unauthenticated.');
    }

    public function test_transactions_returns_only_the_transactions_owned_by_the_authenticated_user_newest_first(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();
        Transaction::factory()->forAccount($account)->create(['description' => 'Older', 'occurred_at' => '2026-09-01 10:00:00']);
        Transaction::factory()->forAccount($account)->create(['description' => 'Newer', 'occurred_at' => '2026-09-03 10:00:00']);
        Transaction::factory()->create(['description' => 'Someone else', 'occurred_at' => '2026-09-05 10:00:00']);
        Sanctum::actingAs($user);

        $response = $this->graphQL(/** @lang GraphQL */ '
            { transactions { data { description } paginatorInfo { total } } }
        ');

        $response->assertGraphQLErrorFree();
        $this->assertSame([['description' => 'Newer'], ['description' => 'Older']], $response->json('data.transactions.data'));
        $this->assertSame(2, $response->json('data.transactions.paginatorInfo.total'));
    }

    public function test_transactions_paginates_with_first_and_page(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();
        Transaction::factory()->forAccount($account)->count(3)->sequence(
            ['description' => 'Third', 'occurred_at' => '2026-09-03 10:00:00'],
            ['description' => 'Second', 'occurred_at' => '2026-09-02 10:00:00'],
            ['description' => 'First', 'occurred_at' => '2026-09-01 10:00:00'],
        )->create();
        Sanctum::actingAs($user);

        $response = $this->graphQL(/** @lang GraphQL */ '
            { transactions(first: 2, page: 2) { data { description } paginatorInfo { total lastPage } } }
        ');

        $response->assertGraphQLErrorFree();
        $this->assertSame([['description' => 'First']], $response->json('data.transactions.data'));
        $response->assertJsonPath('data.transactions.paginatorInfo', ['total' => 3, 'lastPage' => 2]);
    }

    public function test_transactions_filtered_by_account_type_category_and_date_range_returns_only_the_matches(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();
        $otherAccount = Account::factory()->for($user)->create();
        $category = Category::factory()->for($user)->create();
        $match = Transaction::factory()->forAccount($account)->ofType(TransactionType::Expense)
            ->create(['description' => 'Match', 'category_id' => $category->id, 'occurred_at' => '2026-09-15 12:00:00']);
        Transaction::factory()->forAccount($otherAccount)->ofType(TransactionType::Expense)
            ->create(['description' => 'Other account', 'category_id' => $category->id, 'occurred_at' => '2026-09-15 12:00:00']);
        Transaction::factory()->forAccount($account)->ofType(TransactionType::Income)
            ->create(['description' => 'Other type', 'category_id' => $category->id, 'occurred_at' => '2026-09-15 12:00:00']);
        Transaction::factory()->forAccount($account)->ofType(TransactionType::Expense)
            ->create(['description' => 'No category', 'occurred_at' => '2026-09-15 12:00:00']);
        Transaction::factory()->forAccount($account)->ofType(TransactionType::Expense)
            ->create(['description' => 'Too early', 'category_id' => $category->id, 'occurred_at' => '2026-08-31 23:59:59']);
        Transaction::factory()->forAccount($account)->ofType(TransactionType::Expense)
            ->create(['description' => 'Too late', 'category_id' => $category->id, 'occurred_at' => '2026-10-01 00:00:00']);
        Sanctum::actingAs($user);

        $response = $this->graphQL(/** @lang GraphQL */ '
            query ($accountId: ID, $categoryId: ID) {
                transactions(
                    account_id: $accountId, category_id: $categoryId, type: EXPENSE,
                    from: "2026-09-01 00:00:00", to: "2026-09-30 23:59:59"
                ) { data { id description } }
            }
        ', ['accountId' => $account->id, 'categoryId' => $category->id]);

        $response->assertGraphQLErrorFree();
        $this->assertSame([['id' => (string) $match->id, 'description' => 'Match']], $response->json('data.transactions.data'));
    }

    public function test_transaction_returns_the_owned_transaction_with_its_relations(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create(['name' => 'Wallet']);
        $category = Category::factory()->for($user)->create(['name' => 'Food']);
        $transaction = Transaction::factory()
            ->forAccount($account)
            ->ofType(TransactionType::Income)
            ->create([
                'amount' => 75.25,
                'category_id' => $category->id,
                'occurred_at' => '2026-09-10 09:15:00',
                'description' => 'Refund',
            ]);
        Sanctum::actingAs($user);

        $response = $this->graphQL(/** @lang GraphQL */ '
            query ($id: ID!) {
                transaction(id: $id) {
                    id type amount amount_signed occurred_at description
                    account { name } category { name } merchant { id } source { id } currency { id }
                }
            }
        ', ['id' => $transaction->id]);

        $response->assertGraphQLErrorFree();
        $response->assertJsonPath('data.transaction', [
            'id' => (string) $transaction->id,
            'type' => 'INCOME',
            'amount' => 75.25,
            'amount_signed' => 75.25,
            'occurred_at' => '2026-09-10 09:15:00',
            'description' => 'Refund',
            'account' => ['name' => 'Wallet'],
            'category' => ['name' => 'Food'],
            'merchant' => null,
            'source' => null,
            'currency' => ['id' => (string) $account->currency_id],
        ]);
    }

    public function test_transaction_owned_by_another_user_returns_not_found_error(): void
    {
        $otherTransaction = Transaction::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $response = $this->graphQL(/** @lang GraphQL */ '
            query ($id: ID!) { transaction(id: $id) { id } }
        ', ['id' => $otherTransaction->id]);

        $response->assertGraphQLErrorMessage('Transaction not found.');
    }
}
