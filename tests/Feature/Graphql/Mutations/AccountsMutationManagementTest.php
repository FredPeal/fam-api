<?php

namespace Tests\Feature\Graphql\Mutations;

use App\Models\User;
use Fam\Accounts\Enums\AccountTypeId;
use Fam\Accounts\Models\Account;
use Fam\Currencies\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

class AccountsMutationManagementTest extends TestCase
{
    use MakesGraphQLRequests;
    use RefreshDatabase;

    public function test_create_account_stores_the_account_with_enum_type_currency_and_matching_balances(): void
    {
        $user = User::factory()->create();
        $currency = Currency::factory()->code('DOP')->create();
        Sanctum::actingAs($user);

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation {
                createAccount(input: {
                    name: "Main bank", account_type: BANK, currency_code: "DOP", initial_balance: 2500.75
                }) {
                    name initial_balance current_balance accountType { id name } currency { code } user { id }
                }
            }
        ');

        $response->assertGraphQLErrorFree();
        $response->assertJsonPath('data.createAccount', [
            'name' => 'Main bank',
            'initial_balance' => 2500.75,
            'current_balance' => 2500.75,
            'accountType' => ['id' => '2', 'name' => 'Bank Account'],
            'currency' => ['code' => 'DOP'],
            'user' => ['id' => (string) $user->id],
        ]);
        $this->assertDatabaseHas('accounts', [
            'user_id' => $user->id,
            'account_type_id' => AccountTypeId::Bank->value,
            'currency_id' => $currency->id,
            'name' => 'Main bank',
            'initial_balance' => 2500.75,
            'current_balance' => 2500.75,
        ]);
    }

    public function test_create_account_defaults_balances_to_zero_when_initial_balance_is_omitted(): void
    {
        Currency::factory()->code('USD')->create();
        Sanctum::actingAs(User::factory()->create());

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation {
                createAccount(input: { name: "Cash", account_type: CASH, currency_code: "USD" }) {
                    initial_balance current_balance
                }
            }
        ');

        $response->assertGraphQLErrorFree();
        $response->assertJsonPath('data.createAccount', ['initial_balance' => 0, 'current_balance' => 0]);
    }

    public function test_create_account_with_unknown_currency_returns_validation_error_and_creates_nothing(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation {
                createAccount(input: { name: "Cash", account_type: CASH, currency_code: "XXX" }) { id }
            }
        ');

        $response->assertGraphQLValidationError('input.currency_code', 'The selected input.currency code is invalid.');
        $this->assertDatabaseCount('accounts', 0);
    }

    public function test_create_account_with_unknown_account_type_is_rejected_by_the_schema(): void
    {
        Currency::factory()->code('USD')->create();
        Sanctum::actingAs(User::factory()->create());

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation {
                createAccount(input: { name: "Cash", account_type: PIGGY_BANK, currency_code: "USD" }) { id }
            }
        ');

        $this->assertStringContainsString('PIGGY_BANK', (string) $response->json('errors.0.message'));
        $this->assertDatabaseCount('accounts', 0);
    }

    public function test_update_account_changes_the_fields_and_shifts_the_current_balance_by_the_initial_balance_delta(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()
            ->for($user)
            ->for(Currency::factory()->code('USD'))
            ->ofType(AccountTypeId::Cash)
            ->create(['name' => 'Old', 'initial_balance' => 100, 'current_balance' => 40]);
        Currency::factory()->code('DOP')->create();
        Sanctum::actingAs($user);

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation ($id: ID!) {
                updateAccount(id: $id, input: {
                    name: "New", account_type: SAVINGS, currency_code: "DOP", description: "Updated", initial_balance: 150
                }) {
                    name description initial_balance current_balance accountType { id } currency { code }
                }
            }
        ', ['id' => $account->id]);

        $response->assertGraphQLErrorFree();
        $response->assertJsonPath('data.updateAccount', [
            'name' => 'New',
            'description' => 'Updated',
            'initial_balance' => 150,
            'current_balance' => 90,
            'accountType' => ['id' => '3'],
            'currency' => ['code' => 'DOP'],
        ]);
        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'name' => 'New',
            'initial_balance' => 150,
            'current_balance' => 90,
            'account_type_id' => AccountTypeId::Savings->value,
        ]);
    }

    public function test_update_account_owned_by_another_user_returns_not_found_error_and_changes_nothing(): void
    {
        $otherAccount = Account::factory()->for(Currency::factory()->code('USD'))->create(['name' => 'Untouched']);
        Sanctum::actingAs(User::factory()->create());

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation ($id: ID!) {
                updateAccount(id: $id, input: { name: "Hijacked", account_type: CASH, currency_code: "USD" }) { id }
            }
        ', ['id' => $otherAccount->id]);

        $response->assertGraphQLErrorMessage('Account not found.');
        $this->assertDatabaseHas('accounts', ['id' => $otherAccount->id, 'name' => 'Untouched']);
    }

    public function test_delete_account_removes_the_owned_account(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();
        Sanctum::actingAs($user);

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation ($id: ID!) { deleteAccount(id: $id) }
        ', ['id' => $account->id]);

        $response->assertGraphQLErrorFree();
        $response->assertJsonPath('data.deleteAccount', true);
        $this->assertModelMissing($account);
    }

    public function test_delete_account_owned_by_another_user_returns_not_found_error_and_keeps_it(): void
    {
        $otherAccount = Account::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation ($id: ID!) { deleteAccount(id: $id) }
        ', ['id' => $otherAccount->id]);

        $response->assertGraphQLErrorMessage('Account not found.');
        $this->assertModelExists($otherAccount);
    }
}
