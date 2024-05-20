<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\Weekday;
use App\Exceptions\Api\TimeEntryCanNotBeRestartedApiException;
use App\Exceptions\Api\TimeEntryStillRunningApiException;
use App\Http\Requests\V1\TimeEntry\TimeEntryAggregateRequest;
use App\Http\Requests\V1\TimeEntry\TimeEntryIndexRequest;
use App\Http\Requests\V1\TimeEntry\TimeEntryStoreRequest;
use App\Http\Requests\V1\TimeEntry\TimeEntryUpdateMultipleRequest;
use App\Http\Requests\V1\TimeEntry\TimeEntryUpdateRequest;
use App\Http\Resources\V1\TimeEntry\TimeEntryCollection;
use App\Http\Resources\V1\TimeEntry\TimeEntryResource;
use App\Models\Member;
use App\Models\Organization;
use App\Models\TimeEntry;
use App\Service\TimeEntryFilter;
use App\Service\TimezoneService;
use Carbon\CarbonTimeZone;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TimeEntryController extends Controller
{
    protected function checkPermission(Organization $organization, string $permission, ?TimeEntry $timeEntry = null): void
    {
        parent::checkPermission($organization, $permission);
        if ($timeEntry !== null && $timeEntry->organization_id !== $organization->getKey()) {
            throw new AuthorizationException('Time entry does not belong to organization');
        }
    }

    /**
     * Get all time entries in organization
     *
     * If you only need time entries for a specific user, you can filter by `user_id`.
     * Users with the permission `time-entries:view:own` can only use this endpoint with their own user ID in the user_id filter.
     *
     * @throws AuthorizationException
     *
     * @operationId getTimeEntries
     */
    public function index(Organization $organization, TimeEntryIndexRequest $request): JsonResource
    {
        /** @var Member|null $member */
        $member = $request->has('member_id') ? Member::query()->findOrFail($request->get('member_id')) : null;
        if ($member !== null && $member->user_id === Auth::id()) {
            $this->checkPermission($organization, 'time-entries:view:own');
        } else {
            $this->checkPermission($organization, 'time-entries:view:all');
        }

        $timeEntriesQuery = TimeEntry::query()
            ->whereBelongsTo($organization, 'organization')
            ->orderBy('start', 'desc');

        $filter = new TimeEntryFilter($timeEntriesQuery);
        $filter->addBeforeFilter($request->input('before'));
        $filter->addAfterFilter($request->input('after'));
        $filter->addActiveFilter($request->input('active'));
        $filter->addMemberIdFilter($member);
        $filter->addMemberIdsFilter($request->input('member_ids'));
        $filter->addProjectIdsFilter($request->input('project_ids'));
        $filter->addTagIdsFilter($request->input('tag_ids'));
        $filter->addTaskIdsFilter($request->input('task_ids'));
        $filter->addClientIdsFilter($request->input('client_ids'));
        $filter->addBillableFilter($request->input('billable'));

        $limit = $request->has('limit') ? (int) $request->get('limit', 100) : 100;
        if ($limit > 1000) {
            $limit = 1000;
        }
        $timeEntriesQuery->limit($limit);

        $timeEntries = $timeEntriesQuery->get();

        if ($timeEntries->count() === $limit && $request->has('only_full_dates') && (bool) $request->get('only_full_dates') === true) {
            $user = $this->user();
            $timezone = app(TimezoneService::class)->getTimezoneFromUser($user);
            $lastDate = null;
            /** @var TimeEntry $timeEntry */
            foreach ($timeEntries as $timeEntry) {
                if ($lastDate === null || abs($lastDate->diffInDays($timeEntry->start->toImmutable()->timezone($timezone)->startOfDay())) > 0) {
                    $lastDate = $timeEntry->start->toImmutable()->timezone($timezone)->startOfDay();
                }
            }

            $timeEntries = $timeEntries->filter(function (TimeEntry $timeEntry) use ($lastDate, $timezone): bool {
                return $timeEntry->start->toImmutable()->timezone($timezone)->toDateString() !== $lastDate->toDateString();
            });

            if ($timeEntries->count() === 0) {
                Log::warning('User has has more than '.$limit.' time entries on one date', [
                    'date' => $lastDate->toDateString(),
                    'user_id' => $request->input('user_id'),
                    'auth_user_id' => Auth::id(),
                    'limit' => $limit,
                ]);
                $timeEntries = $timeEntriesQuery
                    ->limit(5000)
                    ->where('start', '>=', $lastDate->copy()->startOfDay()->utc())
                    ->where('start', '<=', $lastDate->copy()->endOfDay()->utc())
                    ->get();
            }
        }

        return new TimeEntryCollection($timeEntries);
    }

    /**
     * Get aggregated time entries in organization
     *
     * This endpoint allows you to filter time entries and aggregate them by different criteria.
     * The parameters `group` and `sub_group` allow you to group the time entries by different criteria.
     * If the group parameters are all set to `null` or are all missing, the endpoint will aggregate all filtered time entries.
     *
     * @operationId getAggregatedTimeEntries
     *
     * @return array{
     *     data: array{
     *          grouped_data: null|array<array{
     *              type: string,
     *              key: string|null,
     *              seconds: int,
     *              cost: int,
     *              grouped_data: null|array<array{
     *                  type: string,
     *                  key: string|null,
     *                  seconds: int,
     *                  cost: int
     *              }>
     *          }>,
     *          seconds: int,
     *          cost: int
     *      }
     * }
     *
     * @throws AuthorizationException
     */
    public function aggregate(Organization $organization, TimeEntryAggregateRequest $request): array
    {
        /** @var Member|null $member */
        $member = $request->has('member_id') ? Member::query()->findOrFail($request->get('member_id')) : null;
        if ($member !== null && $member->user_id === Auth::id()) {
            $this->checkPermission($organization, 'time-entries:view:own');
        } else {
            $this->checkPermission($organization, 'time-entries:view:all');
        }

        $timeEntriesQuery = TimeEntry::query()
            ->whereBelongsTo($organization, 'organization');

        $filter = new TimeEntryFilter($timeEntriesQuery);
        $filter->addBeforeFilter($request->input('before'));
        $filter->addAfterFilter($request->input('after'));
        $filter->addActiveFilter($request->input('active'));
        $filter->addMemberIdFilter($member);
        $filter->addMemberIdsFilter($request->input('member_ids'));
        $filter->addProjectIdsFilter($request->input('project_ids'));
        $filter->addTagIdsFilter($request->input('tag_ids'));
        $filter->addTaskIdsFilter($request->input('task_ids'));
        $filter->addClientIdsFilter($request->input('client_ids'));
        $filter->addBillableFilter($request->input('billable'));
        $timeEntriesQuery = $filter->get();

        $user = $this->user();

        /** @var string|null $group1Type */
        $group1Type = $request->get('group');
        /** @var string|null $group2Type */
        $group2Type = $request->get('sub_group');

        $group1Select = null;
        $group2Select = null;
        $groupBy = null;
        if ($group1Type !== null) {
            $group1Select = $this->getGroupByQuery($group1Type, $user->timezone, $user->week_start);
            $groupBy = ['group_1'];
            if ($group2Type !== null) {
                $group2Select = $this->getGroupByQuery($group2Type, $user->timezone, $user->week_start);
                $groupBy = ['group_1', 'group_2'];
            }
        }

        $timeEntriesQuery->selectRaw(
            ($group1Select !== null ? $group1Select.' as group_1,' : '').
            ($group2Select !== null ? $group2Select.' as group_2,' : '').
            ' round(sum(extract(epoch from (coalesce("end", now()) - start)))) as aggregate,'.
            ' round(
                  sum(
                      extract(epoch from (coalesce("end", now()) - start)) * (coalesce(billable_rate, 0)::float/60/60)
                  )
              ) as cost'
        );
        if ($groupBy !== null) {
            $timeEntriesQuery->groupBy($groupBy);
        }

        $timeEntriesAggregates = $timeEntriesQuery->get();

        if ($group1Select !== null) {
            $groupedAggregates = $timeEntriesAggregates->groupBy($group2Select !== null ? ['group_1', 'group_2'] : ['group_1']);

            $group1Response = [];
            $group1ResponseSum = 0;
            $group1ResponseCost = 0;
            foreach ($groupedAggregates as $group1 => $group1Aggregates) {
                /** @var string $group1 */
                $group2Response = [];
                if ($group2Select !== null) {
                    $group2ResponseSum = 0;
                    $group2ResponseCost = 0;
                    foreach ($group1Aggregates as $group2 => $aggregate) {
                        /** @var string $group2 */
                        /** @var Collection<int, object{aggregate: int, cost: int}> $aggregate */
                        /** @var string $group2Type */
                        $group2Response[] = [
                            'type' => $group2Type,
                            'key' => $group2 === '' ? null : (string) $group2,
                            'seconds' => (int) $aggregate->get(0)->aggregate,
                            'cost' => (int) $aggregate->get(0)->cost,
                        ];
                        $group2ResponseSum += (int) $aggregate->get(0)->aggregate;
                        $group2ResponseCost += (int) $aggregate->get(0)->cost;
                    }
                } else {
                    /** @var Collection<int, object{aggregate: int, cost: int}> $group1Aggregates */
                    $group2ResponseSum = (int) $group1Aggregates->get(0)->aggregate;
                    $group2ResponseCost = (int) $group1Aggregates->get(0)->cost;
                    $group2Response = null;
                }

                /** @var string $group1Type */
                $group1Response[] = [
                    'type' => $group1Type,
                    'key' => $group1 === '' ? null : (string) $group1,
                    'seconds' => $group2ResponseSum,
                    'cost' => $group2ResponseCost,
                    'grouped_data' => $group2Response,
                ];
                $group1ResponseSum += $group2ResponseSum;
                $group1ResponseCost += $group2ResponseCost;
            }
        } else {
            $group1Response = null;
            /** @var Collection<int, object{aggregate: int, cost: int}> $timeEntriesAggregates */
            $group1ResponseSum = (int) $timeEntriesAggregates->get(0)->aggregate;
            $group1ResponseCost = (int) $timeEntriesAggregates->get(0)->cost;
        }

        return [
            'data' => [
                'grouped_data' => $group1Response,
                'seconds' => $group1ResponseSum,
                'cost' => $group1ResponseCost,
            ],
        ];
    }

    private function getGroupByQuery(string $group, string $timezone, Weekday $startOfWeek): string
    {
        $timezoneShift = app(TimezoneService::class)->getShiftFromUtc(new CarbonTimeZone($timezone));
        if ($timezoneShift > 0) {
            $dateWithTimeZone = 'start + INTERVAL \''.$timezoneShift.' second\'';
        } elseif ($timezoneShift < 0) {
            $dateWithTimeZone = 'start - INTERVAL \''.abs($timezoneShift).' second\'';
        } else {
            $dateWithTimeZone = 'start';
        }
        $startOfWeek = Carbon::now()->setTimezone($timezone)->startOfWeek($startOfWeek->carbonWeekDay())->utc()->toDateTimeString();
        if ($group === 'day') {
            return 'date('.$dateWithTimeZone.')';
        } elseif ($group === 'week') {
            return "to_char(date_bin('7 days', ".$dateWithTimeZone.", timestamp '".$startOfWeek."'), 'YYYY-MM-DD HH24:MI:SS')";
        } elseif ($group === 'month') {
            return 'to_char('.$dateWithTimeZone.', \'YYYY-MM\')';
        } elseif ($group === 'year') {
            return 'to_char('.$dateWithTimeZone.', \'YYYY\')';
        } elseif ($group === 'user') {
            return 'user_id';
        } elseif ($group === 'project') {
            return 'project_id';
        } elseif ($group === 'task') {
            return 'task_id';
        } elseif ($group === 'client') {
            return 'client_id';
        } elseif ($group === 'billable') {
            return 'billable';
        }

        throw new \LogicException('Invalid group');
    }

    /**
     * Create time entry
     *
     * @throws AuthorizationException
     * @throws TimeEntryStillRunningApiException
     *
     * @operationId createTimeEntry
     */
    public function store(Organization $organization, TimeEntryStoreRequest $request): JsonResource
    {
        /** @var Member $member */
        $member = Member::query()->findOrFail($request->get('member_id'));
        if ($member->user_id === Auth::id()) {
            $this->checkPermission($organization, 'time-entries:create:own');
        } else {
            $this->checkPermission($organization, 'time-entries:create:all');
        }

        if ($request->get('end') === null && TimeEntry::query()->whereBelongsTo($member, 'member')->where('end', null)->exists()) {
            throw new TimeEntryStillRunningApiException();
        }

        $timeEntry = new TimeEntry();
        $timeEntry->fill($request->validated());
        $timeEntry->user_id = $member->user_id;
        $timeEntry->description = $request->get('description') ?? '';
        $timeEntry->organization()->associate($organization);
        $timeEntry->setComputedAttributeValue('billable_rate');
        $timeEntry->save();

        return new TimeEntryResource($timeEntry);
    }

    /**
     * Update time entry
     *
     * @throws AuthorizationException|TimeEntryCanNotBeRestartedApiException
     *
     * @operationId updateTimeEntry
     */
    public function update(Organization $organization, TimeEntry $timeEntry, TimeEntryUpdateRequest $request): JsonResource
    {
        /** @var Member|null $member */
        $member = $request->has('member_id') ? Member::query()->findOrFail($request->get('member_id')) : null;
        if ($timeEntry->member->user_id === Auth::id() && $member?->user_id === Auth::id()) {
            $this->checkPermission($organization, 'time-entries:update:own');
        } else {
            $this->checkPermission($organization, 'time-entries:update:all');
        }

        if ($timeEntry->end !== null && $request->has('end') && $request->get('end') === null) {
            throw new TimeEntryCanNotBeRestartedApiException();
        }

        $timeEntry->fill($request->validated());
        $timeEntry->description = $request->get('description', $timeEntry->description) ?? '';
        $timeEntry->save();

        return new TimeEntryResource($timeEntry);
    }

    /**
     * @throws AuthorizationException
     */
    public function updateMultiple(Organization $organization, TimeEntryUpdateMultipleRequest $request): JsonResponse
    {
        $this->checkAnyPermission($organization, ['time-entries:update:all', 'time-entries:update:own']);
        $canAccessAll = $this->hasPermission($organization, 'time-entries:update:all');

        $ids = $request->get('ids');

        $timeEntries = TimeEntry::query()
            ->whereBelongsTo($organization, 'organization')
            ->whereIn('id', $ids)
            ->get();

        $changes = $request->get('changes');

        if (isset($changes['member_id']) && ! $canAccessAll && $this->member($organization)->getKey() !== $changes['member_id']) {
            throw new AuthorizationException();
        }

        $success = new Collection();
        $error = new Collection();

        foreach ($ids as $id) {
            $timeEntry = $timeEntries->firstWhere('id', $id);
            if ($timeEntry === null) {
                // Note: ID wrong or time entry in different organization
                $error->push($id);

                continue;
            }
            if (! $canAccessAll && $timeEntry->user_id !== Auth::id()) {
                $error->push($id);

                continue;

            }

            $timeEntry->fill($changes);
            $timeEntry->save();
            $success->push($id);
        }

        return response()->json([
            'success' => $success->toArray(),
            'error' => $error->toArray(),
        ]);
    }

    /**
     * Delete time entry
     *
     * @throws AuthorizationException
     *
     * @operationId deleteTimeEntry
     */
    public function destroy(Organization $organization, TimeEntry $timeEntry): JsonResponse
    {
        if ($timeEntry->member->user_id === Auth::id()) {
            $this->checkPermission($organization, 'time-entries:delete:own', $timeEntry);
        } else {
            $this->checkPermission($organization, 'time-entries:delete:all', $timeEntry);
        }

        $timeEntry->delete();

        return response()
            ->json(null, 204);
    }
}
