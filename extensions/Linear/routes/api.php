<?php

declare(strict_types=1);

use Extensions\Linear\Http\Controllers\LinearWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->name('api.v1.linear.')->group(static function (): void {
    // Public webhook endpoint (signature-verified, no auth middleware)
    Route::post('/linear/webhook', [LinearWebhookController::class, 'handle'])->name('webhook');
});
