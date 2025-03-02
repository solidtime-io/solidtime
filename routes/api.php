<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\ApiTokenController;
use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\ExportController;
use App\Http\Controllers\Api\V1\ImportController;
use App\Http\Controllers\Api\V1\InvitationController;
use App\Http\Controllers\Api\V1\MemberController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\ProjectMemberController;
use App\Http\Controllers\Api\V1\Public\ReportController as PublicReportController;
use App\Http\Controllers\Api\V1\ReportController;
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

Route::prefix('v1')->name('v1.')->group(static function (): void {
    Route::middleware([
        'auth:api',
        'verified',
    ])->group(static function (): void {
        // Organization routes
        Route::name('organizations.')->group(static function (): void {
            Route::get('/organizations/{organization}', [OrganizationController::class, 'show'])->name('show');
            Route::put('/organizations/{organization}', [OrganizationController::class, 'update'])->name('update');
        });

        // Member routes
        Route::name('members.')->prefix('/organizations/{organization}')->group(static function (): void {
            Route::get('/members', [MemberController::class, 'index'])->name('index');
            Route::put('/members/{member}', [MemberController::class, 'update'])->name('update');
            Route::delete('/members/{member}', [MemberController::class, 'destroy'])->name('destroy');
            Route::post('/members/{member}/invite-placeholder', [MemberController::class, 'invitePlaceholder'])->name('invite-placeholder');
            Route::post('/members/{member}/make-placeholder', [MemberController::class, 'makePlaceholder'])->name('make-placeholder');
        });

        // User routes
        Route::name('users.')->group(static function (): void {
            Route::get('/users/me', [UserController::class, 'me'])->name('me');
        });

        // Api token routes
        Route::name('api-tokens.')->group(static function (): void {
            Route::get('/users/me/api-tokens', [ApiTokenController::class, 'index'])->name('index');
            Route::post('/users/me/api-tokens', [ApiTokenController::class, 'store'])->name('store');
            Route::post('/users/me/api-tokens/{apiToken}/revoke', [ApiTokenController::class, 'revoke'])->name('revoke');
            Route::delete('/users/me/api-tokens/{apiToken}', [ApiTokenController::class, 'destroy'])->name('destroy');
        });

        // User Member routes
        Route::name('users.memberships.')->group(static function (): void {
            Route::get('/users/me/memberships', [UserMembershipController::class, 'myMemberships'])->name('my-memberships');
        });

        // Invitation routes
        Route::name('invitations.')->prefix('/organizations/{organization}')->group(static function (): void {
            Route::get('/invitations', [InvitationController::class, 'index'])->name('index');
            Route::post('/invitations', [InvitationController::class, 'store'])->name('store')->middleware('check-organization-blocked');
            Route::post('/invitations/{invitation}/resend', [InvitationController::class, 'resend'])->name('resend')->middleware('check-organization-blocked');
            Route::delete('/invitations/{invitation}', [InvitationController::class, 'destroy'])->name('destroy')->middleware('check-organization-blocked');
        });

        // Project routes
        Route::name('projects.')->prefix('/organizations/{organization}')->group(static function (): void {
            Route::get('/projects', [ProjectController::class, 'index'])->name('index');
            Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('show');
            Route::post('/projects', [ProjectController::class, 'store'])->name('store')->middleware('check-organization-blocked');
            Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('update')->middleware('check-organization-blocked');
            Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('destroy');
        });

        // Project member routes
        Route::name('project-members.')->prefix('/organizations/{organization}')->group(static function (): void {
            Route::get('/projects/{project}/project-members', [ProjectMemberController::class, 'index'])->name('index');
            Route::post('/projects/{project}/project-members', [ProjectMemberController::class, 'store'])->name('store')->middleware('check-organization-blocked');
            Route::put('/project-members/{projectMember}', [ProjectMemberController::class, 'update'])->name('update')->middleware('check-organization-blocked');
            Route::delete('/project-members/{projectMember}', [ProjectMemberController::class, 'destroy'])->name('destroy');
        });

        // Time entry routes
        Route::name('time-entries.')->prefix('/organizations/{organization}')->group(static function (): void {
            Route::get('/time-entries', [TimeEntryController::class, 'index'])->name('index');
            Route::get('/time-entries/export', [TimeEntryController::class, 'indexExport'])->name('index-export');
            Route::get('/time-entries/aggregate', [TimeEntryController::class, 'aggregate'])->name('aggregate');
            Route::get('/time-entries/aggregate/export', [TimeEntryController::class, 'aggregateExport'])->name('aggregate-export');
            Route::post('/time-entries', [TimeEntryController::class, 'store'])->name('store')->middleware('check-organization-blocked');
            Route::put('/time-entries/{timeEntry}', [TimeEntryController::class, 'update'])->name('update')->middleware('check-organization-blocked');
            Route::patch('/time-entries', [TimeEntryController::class, 'updateMultiple'])->name('update-multiple')->middleware('check-organization-blocked');
            Route::delete('/time-entries/{timeEntry}', [TimeEntryController::class, 'destroy'])->name('destroy');
            Route::delete('/time-entries', [TimeEntryController::class, 'destroyMultiple'])->name('destroy-multiple');
        });

        Route::name('users.time-entries.')->group(static function (): void {
            Route::get('/users/me/time-entries/active', [UserTimeEntryController::class, 'myActive'])->name('my-active');
        });

        // Report routes
        Route::name('reports.')->prefix('/organizations/{organization}')->group(static function (): void {
            Route::get('/reports', [ReportController::class, 'index'])->name('index');
            Route::get('/reports/{report}', [ReportController::class, 'show'])->name('show');
            Route::post('/reports', [ReportController::class, 'store'])->name('store');
            Route::put('/reports/{report}', [ReportController::class, 'update'])->name('update');
            Route::delete('/reports/{report}', [ReportController::class, 'destroy'])->name('destroy');
        });

        // Tag routes
        Route::name('tags.')->prefix('/organizations/{organization}')->group(static function (): void {
            Route::get('/tags', [TagController::class, 'index'])->name('index');
            Route::post('/tags', [TagController::class, 'store'])->name('store')->middleware('check-organization-blocked');
            Route::put('/tags/{tag}', [TagController::class, 'update'])->name('update')->middleware('check-organization-blocked');
            Route::delete('/tags/{tag}', [TagController::class, 'destroy'])->name('destroy');
        });

        // Client routes
        Route::name('clients.')->prefix('/organizations/{organization}')->group(static function (): void {
            Route::get('/clients', [ClientController::class, 'index'])->name('index');
            Route::post('/clients', [ClientController::class, 'store'])->name('store')->middleware('check-organization-blocked');
            Route::put('/clients/{client}', [ClientController::class, 'update'])->name('update')->middleware('check-organization-blocked');
            Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('destroy');
        });

        // Task routes
        Route::name('tasks.')->prefix('/organizations/{organization}')->group(static function (): void {
            Route::get('/tasks', [TaskController::class, 'index'])->name('index');
            Route::post('/tasks', [TaskController::class, 'store'])->name('store')->middleware('check-organization-blocked');
            Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('update')->middleware('check-organization-blocked');
            Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('destroy');
        });

        // Import routes
        Route::name('import.')->prefix('/organizations/{organization}')->group(static function (): void {
            Route::get('/importers', [ImportController::class, 'index'])->name('index');
            Route::post('/import', [ImportController::class, 'import'])->name('import')->middleware('check-organization-blocked');
        });

        // Export routes
        Route::name('export.')->prefix('/organizations/{organization}')->group(static function (): void {
            Route::post('/export', [ExportController::class, 'export'])->name('export');
        });
    });

    // Public routes
    Route::name('public.')->prefix('/public')->group(static function (): void {
        Route::get('/reports', [PublicReportController::class, 'show'])->name('reports.show');
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
