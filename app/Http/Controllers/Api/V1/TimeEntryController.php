<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\Api\TimeEntryCanNotBeRestartedApiException;
use App\Exceptions\Api\TimeEntryStillRunningApiException;
use App\Http\Requests\V1\TimeEntry\TimeEntryIndexRequest;
use App\Http\Requests\V1\TimeEntry\TimeEntryStoreRequest;
use App\Http\Requests\V1\TimeEntry\TimeEntryUpdateRequest;
use App\Http\Resources\V1\TimeEntry\TimeEntryCollection;
use App\Http\Resources\V1\TimeEntry\TimeEntryResource;
use App\Models\Organization;
use App\Models\TimeEntry;
use App\Service\TimezoneService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
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
     * Get time entries
     *
     * @throws AuthorizationException
     *
     * @operationId getTimeEntries
     */
    public function index(Organization $organization, TimeEntryIndexRequest $request): JsonResource
    {
        if ($request->has('user_id') && $request->get('user_id') === Auth::id()) {
            $this->checkPermission($organization, 'time-entries:view:own');
        } else {
            $this->checkPermission($organization, 'time-entries:view:all');
        }

        $timeEntriesQuery = TimeEntry::query()
            ->whereBelongsTo($organization, 'organization')
            ->orderBy('start', 'desc');

        if ($request->has('before')) {
            $timeEntriesQuery->whereDate('start', '<', $request->input('before'));
        }

        if ($request->has('after')) {
            $timeEntriesQuery->whereDate('start', '>', $request->input('after'));
        }

        if ($request->has('active')) {
            if ($request->get('active') === 'true') {
                $timeEntriesQuery->whereNull('end');
            }
            if ($request->get('active') === 'false') {
                $timeEntriesQuery->whereNotNull('end');
            }
        }

        if ($request->has('user_id')) {
            $timeEntriesQuery->where('user_id', $request->input('user_id'));
        }

        $limit = $request->has('limit') ? (int) $request->get('limit', 150) : null;
        if ($limit !== null) {
            $timeEntriesQuery->limit($limit);
        }

        $timeEntries = $timeEntriesQuery->get();

        if ($timeEntries->count() === $limit && $request->has('only_full_dates') && (bool) $request->get('only_full_dates') === true) {
            $user = Auth::user();
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
                    ->whereDate('start', '=', $lastDate->toDateString())
                    ->get();
            }
        }

        return new TimeEntryCollection($timeEntries);
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
        if ($request->get('user_id') === Auth::id()) {
            $this->checkPermission($organization, 'time-entries:create:own');
        } else {
            $this->checkPermission($organization, 'time-entries:create:all');
        }

        if ($request->get('end') === null && TimeEntry::query()->where('user_id', $request->get('user_id'))->where('end', null)->exists()) {
            throw new TimeEntryStillRunningApiException();
        }

        $timeEntry = new TimeEntry();
        $timeEntry->fill($request->validated());
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
        if ($timeEntry->user_id === Auth::id() && $request->get('user_id') === Auth::id()) {
            $this->checkPermission($organization, 'time-entries:update:own', $timeEntry);
        } else {
            $this->checkPermission($organization, 'time-entries:update:all', $timeEntry);
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
     * Delete time entry
     *
     * @throws AuthorizationException
     *
     * @operationId deleteTimeEntry
     */
    public function destroy(Organization $organization, TimeEntry $timeEntry): JsonResponse
    {
        if ($timeEntry->user_id === Auth::id()) {
            $this->checkPermission($organization, 'time-entries:delete:own', $timeEntry);
        } else {
            $this->checkPermission($organization, 'time-entries:delete:all', $timeEntry);
        }

        $timeEntry->delete();

        return response()
            ->json(null, 204);
    }
}
