<?php

declare(strict_types=1);

use App\Exceptions\Api\PdfRendererIsNotConfiguredException;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\HomeController;
use Gotenberg\Gotenberg;
use Gotenberg\Stream;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Jetstream\Jetstream;

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
    config('jetstream.auth_session'),
    'verified',
])->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');

    Route::get('/time', function () {
        return Inertia::render('Time');
    })->name('time');

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
            'availableRoles' => array_values(Jetstream::$roles),
        ]);
    })->name('members');

    Route::get('/tags', function () {
        return Inertia::render('Tags');
    })->name('tags');

    Route::get('/import', function () {
        return Inertia::render('Import');
    })->name('import');

    Route::get('/pdf-test', function () {
        if (config('services.gotenberg.url') === null) {
            throw new PdfRendererIsNotConfiguredException;
        }
        $viewFile = file_get_contents(resource_path('views/reports/time-entry-aggregate-index.blade.php'));
        $html = Blade::render($viewFile, ['aggregatedData' => []]);
        $footerViewFile = file_get_contents(resource_path('views/reports/time-entry-index-footer.blade.php'));
        $footerHtml = Blade::render($footerViewFile);
        $request = Gotenberg::chromium(config('services.gotenberg.url'))
            ->pdf()
            ->pdfa('PDF/A-3b')
            ->paperSize('8.27', '11.7') // A4
            ->footer(Stream::string('footer', $footerHtml))
            ->html(Stream::string('body', $html));

        return Gotenberg::send($request);
    });

});
