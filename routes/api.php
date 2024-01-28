<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\ProjectController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:api')->prefix('v1')->name('v1.')->group(static function () {
    Route::name('projects.')->group(static function () {
        Route::get('/organization/{organization}/projects', [ProjectController::class, 'index'])->name('index');
        Route::get('/organization/{organization}/projects/{project}', [ProjectController::class, 'show'])->name('show');
        Route::post('/organization/{organization}/projects', [ProjectController::class, 'store'])->name('store');
        Route::put('/organization/{organization}/projects/{project}', [ProjectController::class, 'update'])->name('update');
        Route::delete('/organization/{organization}/projects/{project}', [ProjectController::class, 'destroy'])->name('destroy');
    });

});
