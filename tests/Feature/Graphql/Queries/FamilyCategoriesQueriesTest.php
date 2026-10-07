<?php

namespace Tests\Feature\Graphql\Queries;

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

class FamilyCategoriesQueriesTest extends TestCase
{
    use MakesGraphQLRequests;
    use RefreshDatabase;

    public function test_unauthenticated_request_returns_unauthenticated_error(): void
    {
        $response = $this->graphQL(/** @lang GraphQL */ '
            { familyCategories(family_id: 1) { id } }
        ');

        $response->assertGraphQLErrorMessage('Unauthenticated.');
    }

    public function test_family_categories_returns_the_active_links_of_the_owned_family_sorted_by_category_name(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->for($user)->create();
        $transport = FamilyCategory::factory()
            ->for($family, 'family')
            ->for(Category::factory()->for($user)->state(['name' => 'Transport']), 'category')
            ->create();
        $groceries = FamilyCategory::factory()
            ->for($family, 'family')
            ->for(Category::factory()->for($user)->state(['name' => 'Groceries']), 'category')
            ->create();
        FamilyCategory::factory()->for($family, 'family')->removed()->create();
        FamilyCategory::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->graphQL(/** @lang GraphQL */ '
            query ($familyId: ID!) {
                familyCategories(family_id: $familyId) { id family { id } category { name } }
            }
        ', ['familyId' => $family->id]);

        $response->assertGraphQLErrorFree();
        $this->assertSame([
            ['id' => (string) $groceries->id, 'family' => ['id' => (string) $family->id], 'category' => ['name' => 'Groceries']],
            ['id' => (string) $transport->id, 'family' => ['id' => (string) $family->id], 'category' => ['name' => 'Transport']],
        ], $response->json('data.familyCategories'));
    }

    public function test_family_categories_is_visible_to_a_member_of_the_family(): void
    {
        $member = User::factory()->create();
        $family = Family::factory()->create();
        $family->members()->attach($member->id, ['member_type_id' => MemberType::named(MemberTypeName::Member)->id]);
        FamilyCategory::factory()->for($family, 'family')->create();
        Sanctum::actingAs($member);

        $response = $this->graphQL(/** @lang GraphQL */ '
            query ($familyId: ID!) { familyCategories(family_id: $familyId) { id } }
        ', ['familyId' => $family->id]);

        $response->assertGraphQLErrorFree();
        $this->assertCount(1, $response->json('data.familyCategories'));
    }

    public function test_family_categories_of_a_family_the_user_does_not_belong_to_returns_not_found_error(): void
    {
        $otherFamily = Family::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $response = $this->graphQL(/** @lang GraphQL */ '
            query ($familyId: ID!) { familyCategories(family_id: $familyId) { id } }
        ', ['familyId' => $otherFamily->id]);

        $response->assertGraphQLErrorMessage('Family not found.');
    }
}
