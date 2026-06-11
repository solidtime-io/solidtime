<?php

declare(strict_types=1);

use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\OrganizationController;
use App\Http\Controllers\Web\OrganizationInvitationController;
use App\Http\Controllers\Web\OtherBrowserSessionsController;
use App\Http\Controllers\Web\UserController;
use App\Http\Controllers\Web\UserProfileController;
use App\Service\PermissionStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [HomeController::class, 'index']);

Route::get('/shared-report', function () {
    return Inertia::render('SharedReport');
})->name('shared-report');

Route::middleware([
    'auth:web',
    'auth.session',
    'verified',
])->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');

    Route::get('/time', function () {
        return Inertia::render('Time');
    })->name('time');

    Route::get('/calendar', function () {
        return Inertia::render('Calendar');
    })->name('calendar');

    Route::get('/timesheet', function () {
        return Inertia::render('Timesheet');
    })->name('timesheet');

    Route::get('/reporting', function () {
        return Inertia::render('Reporting');
    })->name('reporting');

    Route::get('/reporting/detailed', function () {
        return Inertia::render('ReportingDetailed');
    })->name('reporting.detailed');

    Route::get('/reporting/shared', function () {
        return Inertia::render('ReportingShared');
    })->name('reporting.shared');

    Route::get('/projects', function () {
        return Inertia::render('Projects');
    })->name('projects');

    Route::get('/projects/{project}', function () {
        return Inertia::render('ProjectShow');
    })->name('projects.show');

    Route::get('/clients', function () {
        return Inertia::render('Clients');
    })->name('clients');

    Route::get('/members', function () {
        return Inertia::render('Members', [
            'availableRoles' => collect(PermissionStore::roleDefinitions())
                ->map(fn (array $definition, string $key): array => [
                    'key' => $key,
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                ])
                ->values()
                ->all(),
        ]);
    })->name('members');

    Route::get('/tags', function () {
        return Inertia::render('Tags');
    })->name('tags');

    Route::get('/import', function () {
        return Inertia::render('Import');
    })->name('import');

    Route::get('/organizations/create', [OrganizationController::class, 'create'])->name('organizations.create');
    Route::get('/organizations/{organizationId}', [OrganizationController::class, 'show'])->name('organizations.show');
    Route::get('/teams/create', function (): RedirectResponse {
        return to_route('organizations.create');
    })->name('teams.create');
    Route::get('/teams/{organizationId}', function (string $organizationId): RedirectResponse {
        return to_route('organizations.show', [$organizationId]);
    })->name('teams.show');
    Route::get('/user/profile', [UserProfileController::class, 'show'])->name('profile.show');
    Route::delete('/user/other-browser-sessions', [OtherBrowserSessionsController::class, 'destroy'])
        ->name('other-browser-sessions.destroy');
});

Route::get('/team-invitations/{invitation}', [OrganizationInvitationController::class, 'accept'])
    ->middleware(['signed'])
    ->name('team-invitations.accept'); // Note: legacy naming
Route::get('/organization-invitations/{invitation}', [OrganizationInvitationController::class, 'accept'])
    ->middleware(['signed:relative'])
    ->name('organization-invitations.accept');

Route::get('/users/{user}/verify-email-change', [UserController::class, 'verifyEmailChange'])
    ->middleware(['auth:web', 'signed:relative'])
    ->name('users.verify-email-change');
