<?php

namespace Tests\Feature\Graphql\Mutations;

use App\Models\User;
use Fam\Categories\Models\Category;
use Fam\Categories\Models\FamilyCategory;
use Fam\Families\Enums\MemberTypeName;
use Fam\Families\Models\Family;
use Fam\Families\Models\MemberType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

class FamilyCategoriesMutationManagementTest extends TestCase
{
    use MakesGraphQLRequests;
    use RefreshDatabase;

    public function test_unauthenticated_request_returns_unauthenticated_error_and_creates_nothing(): void
    {
        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation { createFamilyCategory(input: { family_id: 1, category_id: 1 }) { id } }
        ');

        $response->assertGraphQLErrorMessage('Unauthenticated.');
        $this->assertDatabaseCount('families_categories', 0);
    }

    public function test_create_family_category_shares_an_owned_category_with_an_owned_family(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->for($user)->create();
        $category = Category::factory()->for($user)->create(['name' => 'Groceries']);
        Sanctum::actingAs($user);

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation ($familyId: ID!, $categoryId: ID!) {
                createFamilyCategory(input: { family_id: $familyId, category_id: $categoryId }) {
                    family { id categories { name } } category { id }
                }
            }
        ', ['familyId' => $family->id, 'categoryId' => $category->id]);

        $response->assertGraphQLErrorFree();
        $response->assertJsonPath('data.createFamilyCategory', [
            'family' => ['id' => (string) $family->id, 'categories' => [['name' => 'Groceries']]],
            'category' => ['id' => (string) $category->id],
        ]);
        $this->assertDatabaseHas('families_categories', [
            'families_id' => $family->id,
            'categories_id' => $category->id,
            'deleted_at' => null,
        ]);
    }

    public function test_create_family_category_lets_a_member_share_their_category_with_the_family(): void
    {
        $member = User::factory()->create();
        $family = Family::factory()->create();
        $family->members()->attach($member->id, ['member_type_id' => MemberType::named(MemberTypeName::Member)->id]);
        $category = Category::factory()->for($member)->create();
        Sanctum::actingAs($member);

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation ($familyId: ID!, $categoryId: ID!) {
                createFamilyCategory(input: { family_id: $familyId, category_id: $categoryId }) { id }
            }
        ', ['familyId' => $family->id, 'categoryId' => $category->id]);

        $response->assertGraphQLErrorFree();
        $this->assertDatabaseHas('families_categories', ['families_id' => $family->id, 'categories_id' => $category->id]);
    }

    public function test_create_family_category_restores_a_previously_removed_link_instead_of_duplicating_it(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->for($user)->create();
        $category = Category::factory()->for($user)->create();
        $removed = FamilyCategory::factory()->for($family, 'family')->for($category, 'category')->removed()->create();
        Sanctum::actingAs($user);

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation ($familyId: ID!, $categoryId: ID!) {
                createFamilyCategory(input: { family_id: $familyId, category_id: $categoryId }) { id }
            }
        ', ['familyId' => $family->id, 'categoryId' => $category->id]);

        $response->assertGraphQLErrorFree();
        $response->assertJsonPath('data.createFamilyCategory.id', (string) $removed->id);
        $this->assertDatabaseCount('families_categories', 1);
        $this->assertDatabaseHas('families_categories', ['id' => $removed->id, 'deleted_at' => null]);
    }

    public function test_create_family_category_for_an_already_shared_category_returns_error_and_creates_nothing(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->for($user)->create();
        $category = Category::factory()->for($user)->create();
        FamilyCategory::factory()->for($family, 'family')->for($category, 'category')->create();
        Sanctum::actingAs($user);

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation ($familyId: ID!, $categoryId: ID!) {
                createFamilyCategory(input: { family_id: $familyId, category_id: $categoryId }) { id }
            }
        ', ['familyId' => $family->id, 'categoryId' => $category->id]);

        $response->assertGraphQLErrorMessage('This category is already shared with the family.');
        $this->assertDatabaseCount('families_categories', 1);
    }

    public function test_create_family_category_with_a_family_the_user_does_not_belong_to_returns_not_found_error(): void
    {
        $user = User::factory()->create();
        $otherFamily = Family::factory()->create();
        $category = Category::factory()->for($user)->create();
        Sanctum::actingAs($user);

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation ($familyId: ID!, $categoryId: ID!) {
                createFamilyCategory(input: { family_id: $familyId, category_id: $categoryId }) { id }
            }
        ', ['familyId' => $otherFamily->id, 'categoryId' => $category->id]);

        $response->assertGraphQLErrorMessage('Family not found.');
        $this->assertDatabaseCount('families_categories', 0);
    }

    public function test_create_family_category_with_a_category_owned_by_another_user_returns_not_found_error(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->for($user)->create();
        $otherCategory = Category::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation ($familyId: ID!, $categoryId: ID!) {
                createFamilyCategory(input: { family_id: $familyId, category_id: $categoryId }) { id }
            }
        ', ['familyId' => $family->id, 'categoryId' => $otherCategory->id]);

        $response->assertGraphQLErrorMessage('Category not found.');
        $this->assertDatabaseCount('families_categories', 0);
    }

    public function test_delete_family_category_soft_deletes_the_link_when_the_user_owns_the_family(): void
    {
        $owner = User::factory()->create();
        $family = Family::factory()->for($owner)->create();
        $familyCategory = FamilyCategory::factory()->for($family, 'family')->create();
        Sanctum::actingAs($owner);

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation ($id: ID!) { deleteFamilyCategory(id: $id) }
        ', ['id' => $familyCategory->id]);

        $response->assertGraphQLErrorFree();
        $response->assertJsonPath('data.deleteFamilyCategory', true);
        $this->assertSoftDeleted($familyCategory);
    }

    public function test_delete_family_category_soft_deletes_the_link_when_the_user_owns_the_category(): void
    {
        $categoryOwner = User::factory()->create();
        $familyCategory = FamilyCategory::factory()
            ->for(Category::factory()->for($categoryOwner), 'category')
            ->create();
        Sanctum::actingAs($categoryOwner);

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation ($id: ID!) { deleteFamilyCategory(id: $id) }
        ', ['id' => $familyCategory->id]);

        $response->assertGraphQLErrorFree();
        $this->assertSoftDeleted($familyCategory);
    }

    public function test_delete_family_category_by_a_member_who_owns_neither_returns_not_found_error_and_keeps_it(): void
    {
        $member = User::factory()->create();
        $family = Family::factory()->create();
        $family->members()->attach($member->id, ['member_type_id' => MemberType::named(MemberTypeName::Member)->id]);
        $familyCategory = FamilyCategory::factory()->for($family, 'family')->create();
        Sanctum::actingAs($member);

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation ($id: ID!) { deleteFamilyCategory(id: $id) }
        ', ['id' => $familyCategory->id]);

        $response->assertGraphQLErrorMessage('Family category not found.');
        $this->assertNotSoftDeleted($familyCategory);
    }
}
