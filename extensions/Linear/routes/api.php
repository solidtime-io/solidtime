<?php

declare(strict_types=1);

use Extensions\Linear\Http\Controllers\LinearOAuthController;
use Extensions\Linear\Http\Controllers\LinearWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->middleware('api')->name('api.v1.linear.')->group(static function (): void {
    // Authenticated routes
    Route::middleware(['auth:api', 'verified'])->group(static function (): void {
        Route::prefix('organizations/{organization}')->group(static function (): void {
            Route::get('/linear/connect', [LinearOAuthController::class, 'connect'])->name('connect');
            Route::get('/linear/callback', [LinearOAuthController::class, 'callback'])->name('callback');
            Route::get('/linear/status', [LinearOAuthController::class, 'status'])->name('status');
            Route::delete('/linear/disconnect', [LinearOAuthController::class, 'disconnect'])->name('disconnect');
        });
    });

    // Public webhook endpoint (signature-verified, no auth middleware)
    Route::post('/linear/webhook', [LinearWebhookController::class, 'handle'])->name('webhook');
});
