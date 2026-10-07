<?php

namespace Tests\Feature\Graphql\Queries;

use App\Models\User;
use Fam\Categories\Models\Category;
use Fam\Categories\Models\FamilyCategory;
use Fam\Families\Models\Family;
use Fam\Merchants\Models\Merchant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

class CategoriesQueriesTest extends TestCase
{
    use MakesGraphQLRequests;
    use RefreshDatabase;

    public function test_unauthenticated_request_returns_unauthenticated_error(): void
    {
        $response = $this->graphQL(/** @lang GraphQL */ '
            { categories { id } }
        ');

        $response->assertGraphQLErrorMessage('Unauthenticated.');
    }

    public function test_categories_returns_only_the_categories_owned_by_the_authenticated_user_sorted_by_name(): void
    {
        $user = User::factory()->create();
        Category::factory()->for($user)->create(['name' => 'Transport']);
        Category::factory()->for($user)->create(['name' => 'Groceries']);
        Category::factory()->create(['name' => 'Someone else']);
        Sanctum::actingAs($user);

        $response = $this->graphQL(/** @lang GraphQL */ '
            { categories { name } }
        ');

        $response->assertGraphQLErrorFree();
        $this->assertSame([['name' => 'Groceries'], ['name' => 'Transport']], $response->json('data.categories'));
    }

    public function test_category_returns_the_owned_category_with_the_families_it_is_currently_shared_with(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create(['name' => 'Groceries', 'description' => 'Food', 'icon' => 'cart']);
        $sharedFamily = Family::factory()->create(['name' => 'Shared']);
        $removedFamily = Family::factory()->create(['name' => 'Removed']);
        FamilyCategory::factory()->for($sharedFamily, 'family')->for($category, 'category')->create();
        FamilyCategory::factory()->for($removedFamily, 'family')->for($category, 'category')->removed()->create();
        Merchant::factory()->for($category)->create(['name' => 'Bravo']);
        Sanctum::actingAs($user);

        $response = $this->graphQL(/** @lang GraphQL */ '
            query ($id: ID!) {
                category(id: $id) { id name description icon user { id } families { name } merchants { name } }
            }
        ', ['id' => $category->id]);

        $response->assertGraphQLErrorFree();
        $response->assertJsonPath('data.category', [
            'id' => (string) $category->id,
            'name' => 'Groceries',
            'description' => 'Food',
            'icon' => 'cart',
            'user' => ['id' => (string) $user->id],
            'families' => [['name' => 'Shared']],
            'merchants' => [['name' => 'Bravo']],
        ]);
    }

    public function test_category_owned_by_another_user_returns_not_found_error(): void
    {
        $otherCategory = Category::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $response = $this->graphQL(/** @lang GraphQL */ '
            query ($id: ID!) { category(id: $id) { id } }
        ', ['id' => $otherCategory->id]);

        $response->assertGraphQLErrorMessage('Category not found.');
    }
}
