<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\OAuthAuthorizationCode;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;

class GoogleCallbackController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(): RedirectResponse
    {
        /** @var SocialiteUser $googleUser */
        $googleUser = Socialite::driver('google')->stateless()->user();

        abort_unless(
            filter_var($googleUser->user['email_verified'] ?? false, FILTER_VALIDATE_BOOL),
            403,
            'Google did not verify this email address.',
        );

        $user = User::query()->where('google_id', $googleUser->getId())->first()
            ?? User::query()->where('email', $googleUser->getEmail())->first()
            ?? new User;

        $user->fill([
            'google_id' => $googleUser->getId(),
            'email' => $googleUser->getEmail(),
            'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: $googleUser->getEmail(),
            'first_name' => $googleUser->user['given_name'] ?? null,
            'last_name' => $googleUser->user['family_name'] ?? null,
            'avatar' => $googleUser->getAvatar(),
            'email_verified_at' => now(),
        ]);

        if (! $user->exists) {
            $user->password = Str::password(40);
        }

        $user->save();

        $plainTextCode = Str::random(64);

        OAuthAuthorizationCode::query()->create([
            'user_id' => $user->getKey(),
            'code_hash' => hash('sha256', $plainTextCode),
            'expires_at' => now()->addSeconds((int) config('services.google.authorization_code_ttl', 60)),
        ]);

        $frontendCallbackUrl = config('services.google.frontend_callback_url');

        abort_unless(is_string($frontendCallbackUrl) && $frontendCallbackUrl !== '', 500);

        return redirect()->away($frontendCallbackUrl.'?'.http_build_query([
            'code' => $plainTextCode,
        ]));
    }
}
