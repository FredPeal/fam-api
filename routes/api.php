<?php

use App\Http\Controllers\Api\Auth\ExchangeOAuthCodeController;
use App\Http\Controllers\Api\Auth\GoogleCallbackController;
use App\Http\Controllers\Api\Auth\GoogleRedirectController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::get('/google/redirect', GoogleRedirectController::class)
        ->middleware('throttle:20,1')
        ->name('auth.google.redirect');

    Route::get('/google/callback', GoogleCallbackController::class)
        ->middleware('throttle:20,1')
        ->name('auth.google.callback');

    Route::post('/token', ExchangeOAuthCodeController::class)
        ->middleware('throttle:10,1')
        ->name('auth.token.exchange');
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
