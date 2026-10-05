<?php

namespace Tests\Feature\Graphql;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

class GoogleSignInGraphqlFlowTest extends TestCase
{
    use MakesGraphQLRequests;
    use RefreshDatabase;

    public function test_a_user_who_signs_in_with_google_can_manage_families_with_the_issued_token(): void
    {
        config()->set('services.google.frontend_callback_url', 'https://spa.example.com/auth/google/callback');
        $this->mockGoogleUser(SocialiteUser::fake([
            'id' => 'google-user-123',
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'email_verified' => true,
        ]));

        $callback = $this->get('/api/auth/google/callback');
        $code = $this->codeFromRedirect($callback->headers->get('Location'));
        $token = $this->postJson('/api/auth/token', ['code' => $code])->json('access_token');

        $created = $this->withToken($token)->graphQL(/** @lang GraphQL */ '
            mutation { createFamily(input: { name: "Lovelace Family" }) { id name user { email } } }
        ');
        $created->assertGraphQLErrorFree();
        $created->assertJsonPath('data.createFamily.name', 'Lovelace Family');
        $created->assertJsonPath('data.createFamily.user.email', 'ada@example.com');

        $listed = $this->withToken($token)->graphQL(/** @lang GraphQL */ '
            { families { id name } }
        ');
        $listed->assertGraphQLErrorFree();
        $this->assertSame(
            [['id' => $created->json('data.createFamily.id'), 'name' => 'Lovelace Family']],
            $listed->json('data.families'),
        );

        $user = User::query()->where('email', 'ada@example.com')->sole();
        $this->assertDatabaseHas('families', ['user_id' => $user->id, 'name' => 'Lovelace Family']);
    }

    public function test_graphql_rejects_a_token_that_was_not_issued(): void
    {
        $response = $this->withToken('1|not-a-real-token')->graphQL(/** @lang GraphQL */ '
            { families { id } }
        ');

        $response->assertGraphQLErrorMessage('Unauthenticated.');
    }

    private function mockGoogleUser(SocialiteUser $googleUser): void
    {
        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('stateless')->once()->andReturnSelf();
        $provider->shouldReceive('user')->once()->andReturn($googleUser);

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);
    }

    private function codeFromRedirect(?string $location): string
    {
        $this->assertNotNull($location);

        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        $this->assertArrayHasKey('code', $query);

        return $query['code'];
    }
}
