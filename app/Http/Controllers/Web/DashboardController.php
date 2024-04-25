<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Models\Organization;
use App\Models\User;
use App\Service\DashboardService;
use App\Service\PermissionStore;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function dashboard(DashboardService $dashboardService, PermissionStore $permissionStore): Response
    {
        /** @var User $user */
        $user = auth()->user();
        /** @var Organization $organization */
        $organization = $user->currentTeam;
        $dailyTrackedHours = $dashboardService->getDailyTrackedHours($user, $organization, 60);
        $weeklyHistory = $dashboardService->getWeeklyHistory($user, $organization);
        $totalWeeklyTime = $dashboardService->totalWeeklyTime($user, $organization);
        $totalWeeklyBillableTime = $dashboardService->totalWeeklyBillableTime($user, $organization);
        $totalWeeklyBillableAmount = $dashboardService->totalWeeklyBillableAmount($user, $organization);
        $weeklyProjectOverview = $dashboardService->weeklyProjectOverview($user, $organization);
        $latestTasks = $dashboardService->latestTasks($user, $organization);
        $lastSevenDays = $dashboardService->lastSevenDays($user, $organization);

        $latestTeamActivity = null;
        if ($permissionStore->has($organization, 'time-entries:view:all')) {
            $latestTeamActivity = $dashboardService->latestTeamActivity($organization);
        }

        return Inertia::render('Dashboard', [
            'weeklyProjectOverview' => $weeklyProjectOverview,
            'latestTasks' => $latestTasks,
            'lastSevenDays' => $lastSevenDays,
            'latestTeamActivity' => $latestTeamActivity,
            'dailyTrackedHours' => $dailyTrackedHours,
            'totalWeeklyTime' => $totalWeeklyTime,
            'totalWeeklyBillableTime' => $totalWeeklyBillableTime,
            'totalWeeklyBillableAmount' => $totalWeeklyBillableAmount,
            'weeklyHistory' => $weeklyHistory,
        ]);
    }
}
