<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
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

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware([
    'auth:web',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard', [
            'weeklyProjectOverview' => [
                [
                    'value' => 120,
                    'name' => 'Project 11',
                    'color' => '#26a69a',
                ],
                [
                    'value' => 200,
                    'name' => 'Project 2',
                    'color' => '#d4e157',
                ],
                [
                    'value' => 150,
                    'name' => 'Project 3',
                    'color' => '#ff7043',
                ],
            ],
            'latestTasks' => [
                // the 4 tasks with the most recent time entries
                [
                    'id' => Str::uuid(),
                    'name' => 'Task 1',
                    'project_name' => 'Research',
                    'project_id' => Str::uuid(),
                ],
                [
                    'id' => Str::uuid(),
                    'name' => 'Task 2',
                    'project_name' => 'Research',
                    'project_id' => Str::uuid(),
                ],
                [
                    'id' => Str::uuid(),
                    'name' => 'Task 3',
                    'project_name' => 'Research',
                    'project_id' => Str::uuid(),
                ],
                [
                    'id' => Str::uuid(),
                    'name' => 'Task 4',
                    'project_name' => 'Research',
                    'project_id' => Str::uuid(),
                ],
            ],
            'lastSevenDays' => [
                // the last 7 days with statistics for the time entries
                [
                    'date' => '2024-02-26',
                    'duration' => 3600, // in seconds
                    // if that is too difficult we can just skip that for now
                    'history' => [
                        // duration in s of the 3h windows for the day starting at 00:00
                        300,
                        0,
                        500,
                        0,
                        100,
                        200,
                        100,
                        300,
                    ],
                ],
                [
                    'date' => '2024-02-25',
                    'duration' => 7200, // in seconds
                    'history' => [
                        // duration in s of the 3h windows for the day starting at 00:00
                        300,
                        0,
                        500,
                        0,
                        100,
                        200,
                        100,
                        300,
                    ],
                ],
                [
                    'date' => '2024-02-24',
                    'duration' => 10800, // in seconds
                    'history' => [
                        // duration in s of the 3h windows for the day starting at 00:00
                        300,
                        0,
                        500,
                        0,
                        100,
                        200,
                        100,
                        300,
                    ],
                ],
                [
                    'date' => '2024-02-23',
                    'duration' => 14400, // in seconds
                    'history' => [
                        // duration in s of the 3h windows for the day starting at 00:00
                        300,
                        0,
                        500,
                        0,
                        100,
                        200,
                        100,
                        300,
                    ],
                ],
                [
                    'date' => '2024-02-22',
                    'duration' => 18000, // in seconds
                    'history' => [
                        // duration in s of the 3h windows for the day starting at 00:00
                        300,
                        0,
                        500,
                        0,
                        100,
                        200,
                        100,
                        300,
                    ],
                ],
                [
                    'date' => '2024-02-21',
                    'duration' => 21600, // in seconds
                    'history' => [
                        // duration in s of the 3h windows for the day starting at 00:00
                        300,
                        0,
                        500,
                        0,
                        100,
                        200,
                        100,
                        300,
                    ],
                ],
                [
                    'date' => '2024-02-20',
                    'duration' => 25200, // in seconds
                    'history' => [
                        // duration in s of the 3h windows for the day starting at 00:00
                        300,
                        0,
                        500,
                        0,
                        100,
                        200,
                        100,
                        300,
                    ],
                ],

            ],
            'latestTeamActivity' => [
                // the 4 most recently active members of your team with user_id, name, description of the latest time entry, time_entry_id, task_id and a boolean status if the team member is currently working
                [
                    'user_id' => Str::uuid(),
                    'name' => 'John Doe',
                    'description' => 'Working on the new feature',
                    'time_entry_id' => Str::uuid(),
                    'task_id' => Str::uuid(),
                    'status' => true,
                ],
                [
                    'user_id' => Str::uuid(),
                    'name' => 'Jane Doe',
                    'description' => 'Working on the new feature',
                    'time_entry_id' => Str::uuid(),
                    'task_id' => Str::uuid(),
                    'status' => false,
                ],
                [
                    'user_id' => Str::uuid(),
                    'name' => 'John Smith',
                    'description' => 'Working on the new feature',
                    'time_entry_id' => Str::uuid(),
                    'task_id' => Str::uuid(),
                    'status' => true,
                ],
                [
                    'user_id' => Str::uuid(),
                    'name' => 'Jane Smith',
                    'description' => 'Working on the new feature',
                    'time_entry_id' => Str::uuid(),
                    'task_id' => Str::uuid(),
                    'status' => false,
                ],
            ],
            'dailyTrackedHours' => [
                // not really sure how many days we need here but probably around 60
                // the second value is the duration in seconds
                ['2024-01-21', 10],
                ['2024-01-22', 10],
                ['2024-01-23', 20],
                ['2024-01-24', 10],
                ['2024-01-25', 10],
                ['2024-01-26', 10],
                ['2024-01-27', 20],
                ['2024-01-28', 10],
                ['2024-01-29', 20],
                ['2024-01-30', 10],
                ['2024-01-31', 10],
                ['2024-02-01', 20],
                ['2024-02-02', 20],
                ['2024-02-03', 10],
                ['2024-02-04', 30],
                ['2024-02-05', 10],
                ['2024-02-06', 20],
                ['2024-02-07', 10],
                ['2024-02-08', 30],
                ['2024-02-09', 10],
                ['2024-02-10', 10],
                ['2024-02-11', 10],
                ['2024-02-12', 10],
                ['2024-02-13', 10],
                ['2024-02-14', 20],
                ['2024-02-15', 10],
                ['2024-02-16', 10],
                ['2024-02-17', 10],
                ['2024-02-18', 10],
                ['2024-02-19', 10],
                ['2024-02-20', 30],
                ['2024-02-21', 20],
                ['2024-02-22', 20],
                ['2024-02-23', 30],
                ['2024-02-24', 20],
                ['2024-02-25', 10],
                ['2024-02-26', 10],
                ['2024-02-27', 10],
                ['2024-02-28', 20],
                ['2024-02-29', 10],
                ['2024-03-01', 10],
                ['2024-03-02', 20],
                ['2024-03-03', 10],
                ['2024-03-04', 30],
                ['2024-03-05', 10],
                ['2024-03-06', 20],
                ['2024-03-07', 30],
                ['2024-03-08', 10],
                ['2024-03-09', 20],
                ['2024-03-10', 10],
                ['2024-03-11', 10],
                ['2024-03-12', 10],
                ['2024-03-13', 10],
                ['2024-03-14', 10],
                ['2024-03-15', 10],
                ['2024-03-16', 10],
                ['2024-03-17', 10],
                ['2024-03-18', 10],
                ['2024-03-19', 10],
                ['2024-03-20', 10],
                ['2024-03-21', 10],
                ['2024-03-22', 10],
                ['2024-03-23', 10],
                ['2024-03-24', 10],
                ['2024-03-25', 10],
                ['2024-03-26', 10],
                ['2024-03-27', 10],
                ['2024-03-28', 10],
                ['2024-03-29', 10],
                ['2024-03-30', 10],
                ['2024-03-31', 10],
            ],
            'totalWeeklyTime' => 400,
            'totalWeeklyBillableTime' => 300,
            'totalWeeklyBillableAmount' => [
                'value' => 300.5,
                'currency' => 'USD',
            ],
            'weeklyHistory' => [
                // statistics for the current week starting at Monday / Sunday
                [
                    'date' => '2024-02-26',
                    'duration' => 3600,
                ],
                [
                    'date' => '2024-02-27',
                    'duration' => 2000,
                ],
                [
                    'date' => '2024-02-28',
                    'duration' => 4000,
                ],
                [
                    'date' => '2024-02-29',
                    'duration' => 3000,
                ],
                [
                    'date' => '2024-03-01',
                    'duration' => 5000,
                ],
                [
                    'date' => '2024-03-02',
                    'duration' => 3000,
                ],
                [
                    'date' => '2024-03-03',
                    'duration' => 2000,
                ],
            ],
        ]);
    })->name('dashboard');
});
