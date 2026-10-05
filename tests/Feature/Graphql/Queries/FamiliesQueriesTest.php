<?php

namespace Tests\Feature\Graphql\Queries;

use App\Models\User;
use Fam\Families\Models\Family;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

class FamiliesQueriesTest extends TestCase
{
    use MakesGraphQLRequests;
    use RefreshDatabase;

    public function test_unauthenticated_request_returns_unauthenticated_error(): void
    {
        $response = $this->graphQL(/** @lang GraphQL */ '
            { families { id } }
        ');

        $response->assertGraphQLErrorMessage('Unauthenticated.');
    }

    public function test_families_returns_only_the_families_owned_by_the_authenticated_user_sorted_by_name(): void
    {
        $user = User::factory()->create();
        Family::factory()->for($user)->create(['name' => 'Zeta Family']);
        Family::factory()->for($user)->create(['name' => 'Alpha Family']);
        Family::factory()->create(['name' => 'Other Family']);
        Sanctum::actingAs($user);

        $response = $this->graphQL(/** @lang GraphQL */ '
            { families { name } }
        ');

        $response->assertGraphQLErrorFree();
        $this->assertSame(
            [['name' => 'Alpha Family'], ['name' => 'Zeta Family']],
            $response->json('data.families'),
        );
    }

    public function test_family_returns_the_owned_family_with_its_owner(): void
    {
        $user = User::factory()->create(['name' => 'Ada Lovelace']);
        $family = Family::factory()->for($user)->create([
            'name' => 'Lovelace Family',
            'address' => '12 Byron Street',
        ]);
        Sanctum::actingAs($user);

        $response = $this->graphQL(/** @lang GraphQL */ '
            query ($id: ID!) {
                family(id: $id) { id name address user { name } }
            }
        ', ['id' => $family->id]);

        $response->assertGraphQLErrorFree();
        $response->assertJsonPath('data.family', [
            'id' => (string) $family->id,
            'name' => 'Lovelace Family',
            'address' => '12 Byron Street',
            'user' => ['name' => 'Ada Lovelace'],
        ]);
    }

    public function test_family_owned_by_another_user_returns_not_found_error(): void
    {
        $otherFamily = Family::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $response = $this->graphQL(/** @lang GraphQL */ '
            query ($id: ID!) { family(id: $id) { id } }
        ', ['id' => $otherFamily->id]);

        $response->assertGraphQLErrorMessage('Family not found.');
        $this->assertNull($response->json('data'));
    }
}
