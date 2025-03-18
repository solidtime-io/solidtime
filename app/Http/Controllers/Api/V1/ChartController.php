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

        $dailyTrackedHours = $dashboardService->getDailyTrackedHours($user, $organization, 60);

        return response()->json($dailyTrackedHours);
    }

    /**
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
