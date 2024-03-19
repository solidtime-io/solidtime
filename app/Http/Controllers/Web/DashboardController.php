<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Models\User;
use App\Service\DashboardService;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function dashboard(DashboardService $dashboardService): Response
    {
        /** @var User $user */
        $user = auth()->user();
        $dailyTrackedHours = $dashboardService->getDailyTrackedHours($user, 60);
        $weeklyHistory = $dashboardService->getWeeklyHistory($user);

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
            'dailyTrackedHours' => $dailyTrackedHours,
            'totalWeeklyTime' => 400,
            'totalWeeklyBillableTime' => 300,
            'totalWeeklyBillableAmount' => [
                'value' => 300.5,
                'currency' => 'USD',
            ],
            'weeklyHistory' => $weeklyHistory,
        ]);
    }
}
