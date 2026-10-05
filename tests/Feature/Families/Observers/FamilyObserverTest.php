<?php

namespace Tests\Feature\Families\Observers;

use App\Models\User;
use Fam\Families\Models\Family;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

class FamilyObserverTest extends TestCase
{
    use MakesGraphQLRequests;
    use RefreshDatabase;

    public function test_creating_a_family_generates_its_share_link(): void
    {
        config()->set('services.frontend.url', 'https://app.example.com/');
        config()->set('services.frontend.family_invitation_path', '/join');

        $family = Family::factory()->create();

        $shareLink = $family->shareLink;
        $this->assertNotNull($shareLink);
        $this->assertSame(32, strlen($shareLink->code));
        $this->assertSame('https://app.example.com/join/'.$shareLink->code, $shareLink->full_link);
        $this->assertDatabaseCount('families_share_links', 1);
    }

    public function test_each_family_gets_a_distinct_share_link(): void
    {
        $families = Family::factory()->count(2)->create();

        $this->assertNotSame($families[0]->shareLink->code, $families[1]->shareLink->code);
        $this->assertDatabaseCount('families_share_links', 2);
    }

    public function test_create_family_mutation_returns_the_generated_share_link(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation { createFamily(input: { name: "Lovelace Family" }) { shareLink { code full_link } } }
        ');

        $response->assertGraphQLErrorFree();
        $code = $response->json('data.createFamily.shareLink.code');
        $this->assertSame(32, strlen($code));
        $response->assertJsonPath('data.createFamily.shareLink.full_link', 'http://localhost:3000/families/join/'.$code);
    }
}
