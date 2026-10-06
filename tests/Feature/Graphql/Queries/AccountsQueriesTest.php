<?php

namespace Tests\Feature\Graphql\Queries;

use App\Models\User;
use Database\Seeders\AccountTypeSeeder;
use Fam\Accounts\Enums\AccountTypeId;
use Fam\Accounts\Models\Account;
use Fam\Currencies\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

class AccountsQueriesTest extends TestCase
{
    use MakesGraphQLRequests;
    use RefreshDatabase;

    public function test_unauthenticated_request_returns_unauthenticated_error(): void
    {
        $response = $this->graphQL(/** @lang GraphQL */ '
            { accounts { id } }
        ');

        $response->assertGraphQLErrorMessage('Unauthenticated.');
    }

    public function test_accounts_returns_only_the_accounts_owned_by_the_authenticated_user_sorted_by_name(): void
    {
        $user = User::factory()->create();
        Account::factory()->for($user)->create(['name' => 'Wallet']);
        Account::factory()->for($user)->create(['name' => 'Bank']);
        Account::factory()->create(['name' => 'Someone else']);
        Sanctum::actingAs($user);

        $response = $this->graphQL(/** @lang GraphQL */ '
            { accounts { name } }
        ');

        $response->assertGraphQLErrorFree();
        $this->assertSame([['name' => 'Bank'], ['name' => 'Wallet']], $response->json('data.accounts'));
    }

    public function test_account_returns_the_owned_account_with_its_type_and_currency(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()
            ->for($user)
            ->for(Currency::factory()->code('DOP'))
            ->ofType(AccountTypeId::Savings)
            ->create(['name' => 'Rainy day', 'initial_balance' => 1500.5, 'current_balance' => 1200.25]);
        Sanctum::actingAs($user);

        $response = $this->graphQL(/** @lang GraphQL */ '
            query ($id: ID!) {
                account(id: $id) {
                    id name initial_balance current_balance
                    accountType { id name }
                    currency { code }
                }
            }
        ', ['id' => $account->id]);

        $response->assertGraphQLErrorFree();
        $response->assertJsonPath('data.account', [
            'id' => (string) $account->id,
            'name' => 'Rainy day',
            'initial_balance' => 1500.5,
            'current_balance' => 1200.25,
            'accountType' => ['id' => '3', 'name' => 'Savings Account'],
            'currency' => ['code' => 'DOP'],
        ]);
    }

    public function test_account_owned_by_another_user_returns_not_found_error(): void
    {
        $otherAccount = Account::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $response = $this->graphQL(/** @lang GraphQL */ '
            query ($id: ID!) { account(id: $id) { id } }
        ', ['id' => $otherAccount->id]);

        $response->assertGraphQLErrorMessage('Account not found.');
    }

    public function test_account_types_returns_the_seeded_types_with_ids_matching_the_enum(): void
    {
        $this->seed(AccountTypeSeeder::class);
        Sanctum::actingAs(User::factory()->create());

        $response = $this->graphQL(/** @lang GraphQL */ '
            { accountTypes { id name } }
        ');

        $response->assertGraphQLErrorFree();
        $this->assertSame(
            array_map(fn (AccountTypeId $type): array => ['id' => (string) $type->value, 'name' => $type->label()], AccountTypeId::cases()),
            $response->json('data.accountTypes'),
        );
    }

    public function test_currencies_returns_the_available_currencies_sorted_by_code(): void
    {
        Currency::factory()->code('USD')->create();
        Currency::factory()->code('DOP')->create();
        Sanctum::actingAs(User::factory()->create());

        $response = $this->graphQL(/** @lang GraphQL */ '
            { currencies { code name } }
        ');

        $response->assertGraphQLErrorFree();
        $this->assertSame(
            [['code' => 'DOP', 'name' => 'Dominican Peso'], ['code' => 'USD', 'name' => 'US Dollar']],
            $response->json('data.currencies'),
        );
    }
}
