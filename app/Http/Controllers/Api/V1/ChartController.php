<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\Role;
use App\Models\Organization;
use App\Service\DashboardService;
use App\Service\PermissionStore;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;

class ChartController extends Controller
{
    /**
     * Get chart data for the weekly project overview.
     *
     * @throws AuthorizationException
     *
     * @operationId weeklyProjectOverview
     *
     * @response array<int, array{value: int, name: string, color: string}>
     */
    public function weeklyProjectOverview(Organization $organization, DashboardService $dashboardService): JsonResponse
    {
        $this->checkPermission($organization, 'charts:view:own');
        $user = $this->user();

        $weeklyProjectOverview = $dashboardService->weeklyProjectOverview($user, $organization);

        return response()->json($weeklyProjectOverview);
    }

    /**
     * Get chart data for the latest tasks.
     *
     * @throws AuthorizationException
     *
     * @operationId latestTasks
     *
     * @response array<int, array{task_id: string, name: string, description: string|null, status: bool, time_entry_id: string|null}>
     */
    public function latestTasks(Organization $organization, DashboardService $dashboardService): JsonResponse
    {
        $this->checkPermission($organization, 'charts:view:own');
        $user = $this->user();

        $latestTasks = $dashboardService->latestTasks($user, $organization);

        return response()->json($latestTasks);
    }

    /**
     * Get chart data for the last seven days.
     *
     * @throws AuthorizationException
     *
     * @operationId lastSevenDays
     *
     * @response array<int, array{ date: string, duration: int, history: array<int> }>
     */
    public function lastSevenDays(Organization $organization, DashboardService $dashboardService): JsonResponse
    {
        $this->checkPermission($organization, 'charts:view:own');
        $user = $this->user();

        $lastSevenDays = $dashboardService->lastSevenDays($user, $organization);

        return response()->json($lastSevenDays);
    }

    /**
     * Get chart data for the latest team activity.
     *
     * @throws AuthorizationException
     *
     * @operationId latestTeamActivity
     *
     * @response array<int, array{member_id: string, name: string, description: string|null, time_entry_id: string, task_id: string|null, status: bool }>
     */
    public function latestTeamActivity(Organization $organization, DashboardService $dashboardService, PermissionStore $permissionStore): JsonResponse
    {
        $this->checkPermission($organization, 'charts:view:all');

        $latestTeamActivity = $dashboardService->latestTeamActivity($organization);

        return response()->json($latestTeamActivity);
    }

    /**
     * Get chart data for daily tracked hours.
     *
     * @throws AuthorizationException
     *
     * @operationId dailyTrackedHours
     *
     * @response array<int, array{date: string, duration: int}>
     */
    public function dailyTrackedHours(Organization $organization, DashboardService $dashboardService): JsonResponse
    {
        $this->checkPermission($organization, 'charts:view:own');
        $user = $this->user();

        $dailyTrackedHours = $dashboardService->getDailyTrackedHours($user, $organization, 100);

        return response()->json($dailyTrackedHours);
    }

    /**
     * Get chart data for total weekly time.
     *
     * @throws AuthorizationException
     *
     * @operationId totalWeeklyTime
     *
     * @response int
     */
    public function totalWeeklyTime(Organization $organization, DashboardService $dashboardService): JsonResponse
    {
        $this->checkPermission($organization, 'charts:view:own');
        $user = $this->user();

        $totalWeeklyTime = $dashboardService->totalWeeklyTime($user, $organization);

        return response()->json($totalWeeklyTime);
    }

    /**
     * Get chart data for total weekly billable time.
     *
     * @throws AuthorizationException
     *
     * @operationId totalWeeklyBillableTime
     *
     * @response int
     */
    public function totalWeeklyBillableTime(Organization $organization, DashboardService $dashboardService): JsonResponse
    {
        $this->checkPermission($organization, 'charts:view:own');
        $user = $this->user();

        $totalWeeklyBillableTime = $dashboardService->totalWeeklyBillableTime($user, $organization);

        return response()->json($totalWeeklyBillableTime);
    }

    /**
     * Get chart data for total weekly billable amount.
     *
     * @throws AuthorizationException
     *
     * @operationId totalWeeklyBillableAmount
     *
     * @response array{value: int, currency: string}
     */
    public function totalWeeklyBillableAmount(Organization $organization, DashboardService $dashboardService): JsonResponse
    {
        $this->checkPermission($organization, 'charts:view:own');
        $user = $this->user();

        $showBillableRate = $this->member($organization)->role !== Role::Employee->value || $organization->employees_can_see_billable_rates;
        if (! $showBillableRate) {
            throw new AuthorizationException('You do not have permission to view billable rates.');
        }

        $totalWeeklyBillableAmount = $dashboardService->totalWeeklyBillableAmount($user, $organization);

        return response()->json($totalWeeklyBillableAmount);
    }

    /**
     * Get chart data for weekly history.
     *
     * @throws AuthorizationException
     *
     * @operationId weeklyHistory
     *
     * @response array<int, array{date: string, duration: int}>
     */
    public function weeklyHistory(Organization $organization, DashboardService $dashboardService): JsonResponse
    {
        $this->checkPermission($organization, 'charts:view:own');
        $user = $this->user();

        $weeklyHistory = $dashboardService->getWeeklyHistory($user, $organization);

        return response()->json($weeklyHistory);
    }
}
