<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExchangeOAuthCodeRequest;
use App\Models\OAuthAuthorizationCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExchangeOAuthCodeController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(ExchangeOAuthCodeRequest $request): JsonResponse
    {
        $plainTextToken = DB::transaction(function () use ($request): string {
            $authorizationCode = OAuthAuthorizationCode::query()
                ->where('code_hash', hash('sha256', $request->string('code')->toString()))
                ->lockForUpdate()
                ->first();

            if ($authorizationCode === null || $authorizationCode->used_at !== null || $authorizationCode->expires_at->isPast()) {
                throw ValidationException::withMessages([
                    'code' => ['The authorization code is invalid or has expired.'],
                ]);
            }

            $authorizationCode->update(['used_at' => now()]);

            return $authorizationCode->user
                ->createToken($request->string('token_name', 'spa')->toString())
                ->plainTextToken;
        });

        return response()->json([
            'access_token' => $plainTextToken,
            'token_type' => 'Bearer',
        ]);
    }
}
