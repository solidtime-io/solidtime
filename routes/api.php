<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\ExportController;
use App\Http\Controllers\Api\V1\ImportController;
use App\Http\Controllers\Api\V1\InvitationController;
use App\Http\Controllers\Api\V1\MemberController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\ProjectMemberController;
use App\Http\Controllers\Api\V1\TagController;
use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Controllers\Api\V1\TimeEntryController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\UserMembershipController;
use App\Http\Controllers\Api\V1\UserTimeEntryController;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

Route::middleware([
    'auth:api',
    'verified',
])->prefix('v1')->name('v1.')->group(static function (): void {
    // Organization routes
    Route::name('organizations.')->group(static function (): void {
        Route::get('/organizations/{organization}', [OrganizationController::class, 'show'])->name('show');
        Route::put('/organizations/{organization}', [OrganizationController::class, 'update'])->name('update')->middleware('check-organization-blocked');
    });

    // Member routes
    Route::name('members.')->group(static function (): void {
        Route::get('/organizations/{organization}/members', [MemberController::class, 'index'])->name('index');
        Route::put('/organizations/{organization}/members/{member}', [MemberController::class, 'update'])->name('update');
        Route::delete('/organizations/{organization}/members/{member}', [MemberController::class, 'destroy'])->name('destroy');
        Route::post('/organizations/{organization}/members/{member}/invite-placeholder', [MemberController::class, 'invitePlaceholder'])->name('invite-placeholder');
        Route::post('/organizations/{organization}/members/{member}/make-placeholder', [MemberController::class, 'makePlaceholder'])->name('make-placeholder');
    });

    // User routes
    Route::name('users.')->group(static function (): void {
        Route::get('/users/me', [UserController::class, 'me'])->name('me');
    });

    // User Member routes
    Route::name('users.memberships.')->group(static function (): void {
        Route::get('/users/me/memberships', [UserMembershipController::class, 'myMemberships'])->name('my-memberships');
    });

    // Invitation routes
    Route::name('invitations.')->group(static function (): void {
        Route::get('/organizations/{organization}/invitations', [InvitationController::class, 'index'])->name('index');
        Route::post('/organizations/{organization}/invitations', [InvitationController::class, 'store'])->name('store')->middleware('check-organization-blocked');
        Route::post('/organizations/{organization}/invitations/{invitation}/resend', [InvitationController::class, 'resend'])->name('resend')->middleware('check-organization-blocked');
        Route::delete('/organizations/{organization}/invitations/{invitation}', [InvitationController::class, 'destroy'])->name('destroy')->middleware('check-organization-blocked');
    });

    // Project routes
    Route::name('projects.')->group(static function (): void {
        Route::get('/organizations/{organization}/projects', [ProjectController::class, 'index'])->name('index');
        Route::get('/organizations/{organization}/projects/{project}', [ProjectController::class, 'show'])->name('show');
        Route::post('/organizations/{organization}/projects', [ProjectController::class, 'store'])->name('store')->middleware('check-organization-blocked');
        Route::put('/organizations/{organization}/projects/{project}', [ProjectController::class, 'update'])->name('update')->middleware('check-organization-blocked');
        Route::delete('/organizations/{organization}/projects/{project}', [ProjectController::class, 'destroy'])->name('destroy');
    });

    // Project member routes
    Route::name('project-members.')->group(static function (): void {
        Route::get('/organizations/{organization}/projects/{project}/project-members', [ProjectMemberController::class, 'index'])->name('index');
        Route::post('/organizations/{organization}/projects/{project}/project-members', [ProjectMemberController::class, 'store'])->name('store')->middleware('check-organization-blocked');
        Route::put('/organizations/{organization}/project-members/{projectMember}', [ProjectMemberController::class, 'update'])->name('update')->middleware('check-organization-blocked');
        Route::delete('/organizations/{organization}/project-members/{projectMember}', [ProjectMemberController::class, 'destroy'])->name('destroy');
    });

    // Time entry routes
    Route::name('time-entries.')->group(static function (): void {
        Route::get('/organizations/{organization}/time-entries', [TimeEntryController::class, 'index'])->name('index');
        Route::get('/organizations/{organization}/time-entries/export', [TimeEntryController::class, 'indexExport'])->name('index-export');
        Route::get('/organizations/{organization}/time-entries/aggregate', [TimeEntryController::class, 'aggregate'])->name('aggregate');
        Route::get('/organizations/{organization}/time-entries/aggregate/export', [TimeEntryController::class, 'aggregateExport'])->name('aggregate-export');
        Route::post('/organizations/{organization}/time-entries', [TimeEntryController::class, 'store'])->name('store')->middleware('check-organization-blocked');
        Route::put('/organizations/{organization}/time-entries/{timeEntry}', [TimeEntryController::class, 'update'])->name('update')->middleware('check-organization-blocked');
        Route::patch('/organizations/{organization}/time-entries', [TimeEntryController::class, 'updateMultiple'])->name('update-multiple')->middleware('check-organization-blocked');
        Route::delete('/organizations/{organization}/time-entries/{timeEntry}', [TimeEntryController::class, 'destroy'])->name('destroy');
        Route::delete('/organizations/{organization}/time-entries', [TimeEntryController::class, 'destroyMultiple'])->name('destroy-multiple');
    });

    Route::name('users.time-entries.')->group(static function (): void {
        Route::get('/users/me/time-entries/active', [UserTimeEntryController::class, 'myActive'])->name('my-active');
    });

    // Tag routes
    Route::name('tags.')->group(static function (): void {
        Route::get('/organizations/{organization}/tags', [TagController::class, 'index'])->name('index');
        Route::post('/organizations/{organization}/tags', [TagController::class, 'store'])->name('store')->middleware('check-organization-blocked');
        Route::put('/organizations/{organization}/tags/{tag}', [TagController::class, 'update'])->name('update')->middleware('check-organization-blocked');
        Route::delete('/organizations/{organization}/tags/{tag}', [TagController::class, 'destroy'])->name('destroy');
    });

    // Client routes
    Route::name('clients.')->group(static function (): void {
        Route::get('/organizations/{organization}/clients', [ClientController::class, 'index'])->name('index');
        Route::post('/organizations/{organization}/clients', [ClientController::class, 'store'])->name('store')->middleware('check-organization-blocked');
        Route::put('/organizations/{organization}/clients/{client}', [ClientController::class, 'update'])->name('update')->middleware('check-organization-blocked');
        Route::delete('/organizations/{organization}/clients/{client}', [ClientController::class, 'destroy'])->name('destroy');
    });

    // Task routes
    Route::name('tasks.')->group(static function (): void {
        Route::get('/organizations/{organization}/tasks', [TaskController::class, 'index'])->name('index');
        Route::post('/organizations/{organization}/tasks', [TaskController::class, 'store'])->name('store')->middleware('check-organization-blocked');
        Route::put('/organizations/{organization}/tasks/{task}', [TaskController::class, 'update'])->name('update')->middleware('check-organization-blocked');
        Route::delete('/organizations/{organization}/tasks/{task}', [TaskController::class, 'destroy'])->name('destroy');
    });

    // Import routes
    Route::name('import.')->group(static function (): void {
        Route::get('/organizations/{organization}/importers', [ImportController::class, 'index'])->name('index');
        Route::post('/organizations/{organization}/import', [ImportController::class, 'import'])->name('import')->middleware('check-organization-blocked');
    });

    // Export routes
    Route::name('export.')->prefix('/organizations/{organization}')->group(static function (): void {
        Route::post('/export', [ExportController::class, 'export'])->name('export');
    });
});

/**
 * Fallback routes, to prevent a rendered HTML page in /api/* routes
 * The / route is also included since the fallback is not triggered on the root route
 */
Route::get('/', function (): void {
    throw new NotFoundHttpException('API resource not found');
});
Route::fallback(function (): void {
    throw new NotFoundHttpException('API resource not found');
});
