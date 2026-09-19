<?php

namespace Tests\Feature;

use App\Models\OAuthAuthorizationCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_callback_creates_a_user_and_returns_an_exchange_code_to_the_spa(): void
    {
        config()->set('services.google.frontend_callback_url', 'https://spa.example.com/auth/google/callback');
        $this->mockGoogleUser(SocialiteUser::fake([
            'id' => 'google-user-123',
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'avatar' => 'https://example.com/ada.jpg',
            'email_verified' => true,
            'given_name' => 'Ada',
            'family_name' => 'Lovelace',
        ]));

        $response = $this->get('/api/auth/google/callback');

        $response->assertRedirectContains('https://spa.example.com/auth/google/callback?code=');

        $user = User::query()->sole();
        $this->assertSame('google-user-123', $user->google_id);
        $this->assertSame('Ada', $user->first_name);
        $this->assertSame('Lovelace', $user->last_name);
        $this->assertNotNull($user->email_verified_at);

        $code = $this->codeFromRedirect($response->headers->get('Location'));

        $this->assertDatabaseHas('oauth_authorization_codes', [
            'user_id' => $user->id,
            'code_hash' => hash('sha256', $code),
            'used_at' => null,
        ]);
    }

    public function test_an_authorization_code_can_be_exchanged_only_once_for_a_bearer_token(): void
    {
        $user = User::factory()->create();
        $code = Str::random(64);

        OAuthAuthorizationCode::query()->create([
            'user_id' => $user->id,
            'code_hash' => hash('sha256', $code),
            'expires_at' => now()->addMinute(),
        ]);

        $response = $this->postJson('/api/auth/token', [
            'code' => $code,
            'token_name' => 'web-spa',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonStructure(['access_token']);

        $this->withToken($response->json('access_token'))
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('id', $user->id);

        $this->postJson('/api/auth/token', ['code' => $code])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    }

    public function test_an_expired_authorization_code_cannot_be_exchanged(): void
    {
        $code = Str::random(64);

        OAuthAuthorizationCode::query()->create([
            'user_id' => User::factory()->create()->id,
            'code_hash' => hash('sha256', $code),
            'expires_at' => now()->subSecond(),
        ]);

        $this->postJson('/api/auth/token', ['code' => $code])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    }

    public function test_google_callback_rejects_an_unverified_email(): void
    {
        $this->mockGoogleUser(SocialiteUser::fake([
            'email_verified' => false,
        ]));

        $this->get('/api/auth/google/callback')->assertForbidden();

        $this->assertDatabaseEmpty('users');
        $this->assertDatabaseEmpty('oauth_authorization_codes');
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
