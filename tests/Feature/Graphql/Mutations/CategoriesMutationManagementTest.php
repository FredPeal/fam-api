<?php

namespace Tests\Feature\Graphql\Mutations;

use App\Models\User;
use Fam\Categories\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

class CategoriesMutationManagementTest extends TestCase
{
    use MakesGraphQLRequests;
    use RefreshDatabase;

    public function test_unauthenticated_request_returns_unauthenticated_error_and_creates_nothing(): void
    {
        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation { createCategory(input: { name: "Groceries" }) { id } }
        ');

        $response->assertGraphQLErrorMessage('Unauthenticated.');
        $this->assertDatabaseCount('categories', 0);
    }

    public function test_create_category_stores_the_category_owned_by_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation {
                createCategory(input: { name: "Groceries", description: "Food and drinks", icon: "cart" }) {
                    name description icon user { id }
                }
            }
        ');

        $response->assertGraphQLErrorFree();
        $response->assertJsonPath('data.createCategory', [
            'name' => 'Groceries',
            'description' => 'Food and drinks',
            'icon' => 'cart',
            'user' => ['id' => (string) $user->id],
        ]);
        $this->assertDatabaseHas('categories', [
            'user_id' => $user->id,
            'name' => 'Groceries',
            'description' => 'Food and drinks',
            'icon' => 'cart',
        ]);
    }

    public function test_create_category_with_blank_name_returns_validation_error_and_creates_nothing(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation { createCategory(input: { name: "" }) { id } }
        ');

        $response->assertGraphQLValidationError('input.name', 'The input.name field is required.');
        $this->assertDatabaseCount('categories', 0);
    }

    public function test_create_category_with_a_name_the_user_already_uses_returns_error_and_creates_nothing(): void
    {
        $user = User::factory()->create();
        Category::factory()->for($user)->create(['name' => 'Groceries']);
        Sanctum::actingAs($user);

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation { createCategory(input: { name: "Groceries" }) { id } }
        ');

        $response->assertGraphQLErrorMessage('You already have a category named "Groceries".');
        $this->assertDatabaseCount('categories', 1);
    }

    public function test_create_category_allows_a_name_another_user_already_uses(): void
    {
        Category::factory()->create(['name' => 'Groceries']);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation { createCategory(input: { name: "Groceries" }) { id } }
        ');

        $response->assertGraphQLErrorFree();
        $this->assertDatabaseHas('categories', ['user_id' => $user->id, 'name' => 'Groceries']);
    }

    public function test_update_category_changes_the_owned_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create(['name' => 'Old', 'description' => 'Old description', 'icon' => 'old']);
        Sanctum::actingAs($user);

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation ($id: ID!) {
                updateCategory(id: $id, input: { name: "New", description: "New description" }) {
                    name description icon
                }
            }
        ', ['id' => $category->id]);

        $response->assertGraphQLErrorFree();
        $response->assertJsonPath('data.updateCategory', ['name' => 'New', 'description' => 'New description', 'icon' => null]);
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'New',
            'description' => 'New description',
            'icon' => null,
        ]);
    }

    public function test_update_category_keeping_its_own_name_succeeds(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create(['name' => 'Groceries']);
        Sanctum::actingAs($user);

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation ($id: ID!) {
                updateCategory(id: $id, input: { name: "Groceries", description: "Updated" }) { description }
            }
        ', ['id' => $category->id]);

        $response->assertGraphQLErrorFree();
        $response->assertJsonPath('data.updateCategory.description', 'Updated');
    }

    public function test_update_category_to_a_name_the_user_already_uses_returns_error_and_changes_nothing(): void
    {
        $user = User::factory()->create();
        Category::factory()->for($user)->create(['name' => 'Groceries']);
        $category = Category::factory()->for($user)->create(['name' => 'Transport']);
        Sanctum::actingAs($user);

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation ($id: ID!) { updateCategory(id: $id, input: { name: "Groceries" }) { id } }
        ', ['id' => $category->id]);

        $response->assertGraphQLErrorMessage('You already have a category named "Groceries".');
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Transport']);
    }

    public function test_update_category_owned_by_another_user_returns_not_found_error_and_changes_nothing(): void
    {
        $otherCategory = Category::factory()->create(['name' => 'Untouched']);
        Sanctum::actingAs(User::factory()->create());

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation ($id: ID!) { updateCategory(id: $id, input: { name: "Hijacked" }) { id } }
        ', ['id' => $otherCategory->id]);

        $response->assertGraphQLErrorMessage('Category not found.');
        $this->assertDatabaseHas('categories', ['id' => $otherCategory->id, 'name' => 'Untouched']);
    }

    public function test_delete_category_removes_the_owned_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        Sanctum::actingAs($user);

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation ($id: ID!) { deleteCategory(id: $id) }
        ', ['id' => $category->id]);

        $response->assertGraphQLErrorFree();
        $response->assertJsonPath('data.deleteCategory', true);
        $this->assertModelMissing($category);
    }

    public function test_delete_category_owned_by_another_user_returns_not_found_error_and_keeps_it(): void
    {
        $otherCategory = Category::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation ($id: ID!) { deleteCategory(id: $id) }
        ', ['id' => $otherCategory->id]);

        $response->assertGraphQLErrorMessage('Category not found.');
        $this->assertModelExists($otherCategory);
    }
}
