<?php

namespace Tests\Feature\Graphql\Queries;

use App\Models\User;
use Fam\Sources\Models\Source;
use Fam\Sources\Models\SourceType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

class SourcesQueriesTest extends TestCase
{
    use MakesGraphQLRequests;
    use RefreshDatabase;

    public function test_unauthenticated_request_returns_unauthenticated_error(): void
    {
        $response = $this->graphQL(/** @lang GraphQL */ '
            { sources { id } }
        ');

        $response->assertGraphQLErrorMessage('Unauthenticated.');
    }

    public function test_sources_returns_only_the_sources_owned_by_the_authenticated_user_sorted_by_name_with_their_type(): void
    {
        $user = User::factory()->create();
        $manual = SourceType::factory()->create(['name' => 'manual']);
        Source::factory()->for($user)->for($manual)->create(['name' => 'Manual']);
        Source::factory()->for($user)->for($manual)->create(['name' => 'Gmail']);
        Source::factory()->create(['name' => 'Someone else']);
        Sanctum::actingAs($user);

        $response = $this->graphQL(/** @lang GraphQL */ '
            { sources { name sourceType { name } } }
        ');

        $response->assertGraphQLErrorFree();
        $this->assertSame([
            ['name' => 'Gmail', 'sourceType' => ['name' => 'manual']],
            ['name' => 'Manual', 'sourceType' => ['name' => 'manual']],
        ], $response->json('data.sources'));
    }
}
