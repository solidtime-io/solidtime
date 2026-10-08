<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\GoalType;
use App\Exceptions\Api\FeatureIsNotAvailableInFreePlanApiException;
use App\Http\Requests\V1\Goal\GoalIndexRequest;
use App\Http\Requests\V1\Goal\GoalStoreRequest;
use App\Http\Requests\V1\Goal\GoalUpdateRequest;
use App\Http\Resources\V1\Goal\GoalCollection;
use App\Http\Resources\V1\Goal\GoalResource;
use App\Models\Goal;
use App\Models\Organization;
use App\Service\BillingContract;
use App\Service\GoalProgressService;
use App\Service\GoalsContract;
use App\Service\TimezoneService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class GoalController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    protected function checkPermission(Organization $organization, string $permission, ?Goal $goal = null): void
    {
        parent::checkPermission($organization, $permission);
        if ($goal !== null) {
            $this->checkGoalBelongsToOrganization($organization, $goal);
        }
    }

    /**
     * @throws AuthorizationException
     */
    private function checkGoalBelongsToOrganization(Organization $organization, Goal $goal): void
    {
        if ($goal->organization_id !== $organization->id) {
            throw new AuthorizationException('Goal does not belong to organization');
        }
    }

    /**
     * Enforces the goal limit provided by the BillingContract, if any. Archived goals count toward the limit.
     *
     * @throws FeatureIsNotAvailableInFreePlanApiException
     */
    private function checkGoalLimit(Organization $organization): void
    {
        $limit = app(BillingContract::class)->getGoalLimit($organization);
        if ($limit === null) {
            return;
        }
        $goals = Goal::query()
            ->whereBelongsTo($organization, 'organization')
            ->count();
        if ($goals >= $limit) {
            throw new FeatureIsNotAvailableInFreePlanApiException;
        }
    }

    /**
     * Get goals
     *
     * Returns the goals the current member is allowed to see, including the progress in the current period.
     *
     * @throws AuthorizationException
     *
     * @operationId getGoals
     */
    public function index(Organization $organization, GoalIndexRequest $request, GoalsContract $access, GoalProgressService $progressService): GoalCollection
    {
        // An organization goal is visible to the member it is for, who does not hold the :organization-type permission.
        // Either permission is enough, the access contract decides which goals come back.
        $this->checkAnyPermission($organization, ['goals:view:own', 'goals:view:organization-type']);
        $member = $this->member($organization);

        $query = Goal::query()
            ->whereBelongsTo($organization, 'organization')
            ->with(['member.user'])
            ->orderBy('created_at', 'desc')
            ->orderBy('id');
        $query = $access->scopeVisibleGoals($query, $member);
        if ($request->getType() !== null) {
            $query->where('type', '=', $request->getType()->value);
        }
        if ($request->getArchivedFilter() === 'true') {
            $query->archived();
        } elseif ($request->getArchivedFilter() === 'false') {
            $query->notArchived();
        }

        $goals = $query->paginate(config('app.pagination_per_page_default'));
        $progressByGoalId = $progressService->getCurrentProgressForGoals($goals->getCollection(), Carbon::now());

        return new GoalCollection($goals, $progressByGoalId);
    }

    /**
     * Get goal
     *
     * @throws AuthorizationException
     *
     * @operationId getGoal
     */
    public function show(Organization $organization, Goal $goal, GoalsContract $access, GoalProgressService $progressService): GoalResource
    {
        // Either permission is enough, see index
        $this->checkAnyPermission($organization, ['goals:view:own', 'goals:view:organization-type']);
        $this->checkGoalBelongsToOrganization($organization, $goal);
        $member = $this->member($organization);
        if (! $access->canViewGoal($member, $goal)) {
            throw new AuthorizationException;
        }
        $goal->load('member.user');

        return new GoalResource($goal, $progressService->getCurrentProgress($goal, Carbon::now()));
    }

    /**
     * Create goal
     *
     * @throws AuthorizationException|FeatureIsNotAvailableInFreePlanApiException
     *
     * @operationId createGoal
     */
    public function store(Organization $organization, GoalStoreRequest $request, GoalsContract $access, GoalProgressService $progressService): JsonResponse
    {
        if ($request->getType() === GoalType::Personal) {
            $this->checkPermission($organization, 'goals:create:own');
        } else {
            $this->checkPermission($organization, 'goals:create:organization-type');
        }
        $member = $this->member($organization);

        $type = $request->getType();
        $targetMemberId = $request->hasMemberId() ? $request->getMemberId() : $member->getKey();
        if (! $access->canCreateGoal($member, $type, $targetMemberId)) {
            throw new AuthorizationException;
        }
        $this->checkGoalLimit($organization);

        $user = $member->user;
        $goal = new Goal;
        $goal->name = $request->getName();
        $goal->type = $type;
        $goal->comparison = $request->getComparison();
        $goal->target_seconds = $request->getTargetSeconds();
        $goal->period = $request->getPeriod();
        $goal->filters = $request->getFilters();
        $goal->timezone = $request->getTimezone() ?? app(TimezoneService::class)->getTimezoneFromUser($user)->getName();
        $goal->week_start = $request->getWeekStart() ?? $user->week_start;
        $goal->organization()->associate($organization);
        $goal->member_id = $targetMemberId;
        $goal->save();
        $goal->load('member.user');

        return (new GoalResource($goal, $progressService->getCurrentProgress($goal, Carbon::now())))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update goal
     *
     * The type of a goal and the member it is for can not be changed after creation.
     *
     * @throws AuthorizationException
     *
     * @operationId updateGoal
     */
    public function update(Organization $organization, Goal $goal, GoalUpdateRequest $request, GoalsContract $access, GoalProgressService $progressService): GoalResource
    {
        if ($goal->type === GoalType::Personal) {
            $this->checkPermission($organization, 'goals:update:own', $goal);
        } else {
            $this->checkPermission($organization, 'goals:update:organization-type', $goal);
        }
        $member = $this->member($organization);
        if (! $access->canUpdateGoal($member, $goal)) {
            throw new AuthorizationException;
        }
        if ($request->has('filters')) {
            $goal->filters = $request->getFilters();
        }

        if ($request->has('name')) {
            $goal->name = $request->getName();
        }
        if ($request->has('comparison')) {
            $goal->comparison = $request->getComparison();
        }
        if ($request->has('target_seconds')) {
            $goal->target_seconds = $request->getTargetSeconds();
        }
        if ($request->has('period')) {
            $goal->period = $request->getPeriod();
        }
        if ($request->has('timezone')) {
            $goal->timezone = $request->getTimezone();
        }
        if ($request->has('week_start')) {
            $goal->week_start = $request->getWeekStart();
        }
        if ($request->has('is_archived')) {
            $goal->archived_at = $request->getIsArchived() ? Carbon::now() : null;
        }
        $goal->save();
        $goal->load('member.user');

        return new GoalResource($goal, $progressService->getCurrentProgress($goal, Carbon::now()));
    }

    /**
     * Delete goal
     *
     * @throws AuthorizationException
     *
     * @operationId deleteGoal
     */
    public function destroy(Organization $organization, Goal $goal, GoalsContract $access): JsonResponse
    {
        if ($goal->type === GoalType::Personal) {
            $this->checkPermission($organization, 'goals:delete:own', $goal);
        } else {
            $this->checkPermission($organization, 'goals:delete:organization-type', $goal);
        }
        $member = $this->member($organization);
        if (! $access->canDeleteGoal($member, $goal)) {
            throw new AuthorizationException;
        }

        $goal->delete();

        return response()->json(null, 204);
    }
}
