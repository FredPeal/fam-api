<?php

namespace Tests\Feature\Graphql\Queries;

use App\Models\User;
use Fam\Categories\Models\Category;
use Fam\Merchants\Models\Merchant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

class MerchantsQueriesTest extends TestCase
{
    use MakesGraphQLRequests;
    use RefreshDatabase;

    public function test_unauthenticated_request_returns_unauthenticated_error(): void
    {
        $response = $this->graphQL(/** @lang GraphQL */ '
            { merchants { id } }
        ');

        $response->assertGraphQLErrorMessage('Unauthenticated.');
    }

    public function test_merchants_returns_only_the_merchants_owned_by_the_authenticated_user_sorted_by_name(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        Merchant::factory()->for($category)->create(['name' => 'Uber']);
        Merchant::factory()->for($category)->create(['name' => 'Amazon']);
        Merchant::factory()->create(['name' => 'Someone else']);
        Sanctum::actingAs($user);

        $response = $this->graphQL(/** @lang GraphQL */ '
            { merchants { name } }
        ');

        $response->assertGraphQLErrorFree();
        $this->assertSame([['name' => 'Amazon'], ['name' => 'Uber']], $response->json('data.merchants'));
    }

    public function test_merchants_filtered_by_category_returns_only_the_merchants_of_that_category(): void
    {
        $user = User::factory()->create();
        $groceries = Category::factory()->for($user)->create();
        $transport = Category::factory()->for($user)->create();
        Merchant::factory()->for($groceries)->create(['name' => 'Bravo']);
        Merchant::factory()->for($transport)->create(['name' => 'Uber']);
        Sanctum::actingAs($user);

        $response = $this->graphQL(/** @lang GraphQL */ '
            query ($categoryId: ID) { merchants(category_id: $categoryId) { name } }
        ', ['categoryId' => $groceries->id]);

        $response->assertGraphQLErrorFree();
        $this->assertSame([['name' => 'Bravo']], $response->json('data.merchants'));
    }

    public function test_merchants_filtered_by_a_category_owned_by_another_user_returns_nothing(): void
    {
        $otherCategory = Category::factory()->create();
        Merchant::factory()->for($otherCategory)->create();
        Sanctum::actingAs(User::factory()->create());

        $response = $this->graphQL(/** @lang GraphQL */ '
            query ($categoryId: ID) { merchants(category_id: $categoryId) { name } }
        ', ['categoryId' => $otherCategory->id]);

        $response->assertGraphQLErrorFree();
        $this->assertSame([], $response->json('data.merchants'));
    }

    public function test_merchant_returns_the_owned_merchant_with_its_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create(['name' => 'Shopping']);
        $merchant = Merchant::factory()->for($category)->create(['code' => 'AMZN', 'name' => 'Amazon', 'description' => 'Online store']);
        Sanctum::actingAs($user);

        $response = $this->graphQL(/** @lang GraphQL */ '
            query ($id: ID!) {
                merchant(id: $id) { id code name description category { id name } }
            }
        ', ['id' => $merchant->id]);

        $response->assertGraphQLErrorFree();
        $response->assertJsonPath('data.merchant', [
            'id' => (string) $merchant->id,
            'code' => 'AMZN',
            'name' => 'Amazon',
            'description' => 'Online store',
            'category' => ['id' => (string) $category->id, 'name' => 'Shopping'],
        ]);
    }

    public function test_merchant_owned_by_another_user_returns_not_found_error(): void
    {
        $otherMerchant = Merchant::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $response = $this->graphQL(/** @lang GraphQL */ '
            query ($id: ID!) { merchant(id: $id) { id } }
        ', ['id' => $otherMerchant->id]);

        $response->assertGraphQLErrorMessage('Merchant not found.');
    }
}
