<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\ExportFormat;
use App\Enums\Role;
use App\Exceptions\Api\FeatureIsNotAvailableInFreePlanApiException;
use App\Exceptions\Api\PdfRendererIsNotConfiguredException;
use App\Exceptions\Api\TimeEntryCanNotBeRestartedApiException;
use App\Exceptions\Api\TimeEntryStillRunningApiException;
use App\Http\Requests\V1\TimeEntry\TimeEntryAggregateExportRequest;
use App\Http\Requests\V1\TimeEntry\TimeEntryAggregateRequest;
use App\Http\Requests\V1\TimeEntry\TimeEntryDestroyMultipleRequest;
use App\Http\Requests\V1\TimeEntry\TimeEntryIndexExportRequest;
use App\Http\Requests\V1\TimeEntry\TimeEntryIndexRequest;
use App\Http\Requests\V1\TimeEntry\TimeEntryStoreRequest;
use App\Http\Requests\V1\TimeEntry\TimeEntryUpdateMultipleRequest;
use App\Http\Requests\V1\TimeEntry\TimeEntryUpdateRequest;
use App\Http\Resources\V1\TimeEntry\TimeEntryCollection;
use App\Http\Resources\V1\TimeEntry\TimeEntryResource;
use App\Jobs\RecalculateSpentTimeForProject;
use App\Jobs\RecalculateSpentTimeForTask;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Service\ReportExport\TimeEntriesDetailedCsvExport;
use App\Service\ReportExport\TimeEntriesDetailedExport;
use App\Service\ReportExport\TimeEntriesReportExport;
use App\Service\TimeEntryAggregationService;
use App\Service\TimeEntryFilter;
use App\Service\TimezoneService;
use Gotenberg\Exceptions\GotenbergApiErrored;
use Gotenberg\Exceptions\NoOutputFileInResponse;
use Gotenberg\Gotenberg;
use Gotenberg\Stream;
use GuzzleHttp\Client;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\File;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\TemporaryDirectory\TemporaryDirectory;

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
     * Get time entries in organization
     *
     * If you only need time entries for a specific user, you can filter by `user_id`.
     * Users with the permission `time-entries:view:own` can only use this endpoint with their own user ID in the user_id filter.
     *
     * @return TimeEntryCollection<TimeEntryResource>
     *
     * @throws AuthorizationException
     *
     * @operationId getTimeEntries
     */
    public function index(Organization $organization, TimeEntryIndexRequest $request): JsonResource
    {
        /** @var Member|null $member */
        $member = $request->has('member_id') ? Member::query()->findOrFail($request->input('member_id')) : null;
        if ($member !== null && $member->user_id === Auth::id()) {
            $this->checkPermission($organization, 'time-entries:view:own');
        } else {
            $this->checkPermission($organization, 'time-entries:view:all');
        }

        $timeEntriesQuery = $this->getTimeEntriesQuery($organization, $request, $member);

        $totalCount = $timeEntriesQuery->count();

        $limit = $request->getLimit();
        if ($limit > 1000) {
            $limit = 1000;
        }
        $timeEntriesQuery->limit($limit);
        $timeEntriesQuery->skip($request->getOffset());

        $timeEntries = $timeEntriesQuery->get();

        if ($timeEntries->count() === $limit && $request->getOnlyFullDates()) {
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

        return (new TimeEntryCollection($timeEntries))
            ->additional([
                'meta' => [
                    'total' => $totalCount,
                ],
            ]);
    }

    /**
     * @return Builder<TimeEntry>
     */
    private function getTimeEntriesQuery(Organization $organization, TimeEntryIndexRequest|TimeEntryIndexExportRequest $request, ?Member $member): Builder
    {
        $timeEntriesQuery = TimeEntry::query()
            ->whereBelongsTo($organization, 'organization')
            ->orderBy('start', 'desc');

        $filter = new TimeEntryFilter($timeEntriesQuery);
        $filter->addStartFilter($request->input('start'));
        $filter->addEndFilter($request->input('end'));
        $filter->addActiveFilter($request->input('active'));
        $filter->addMemberIdFilter($member);
        $filter->addMemberIdsFilter($request->input('member_ids'));
        $filter->addProjectIdsFilter($request->input('project_ids'));
        $filter->addTagIdsFilter($request->input('tag_ids'));
        $filter->addTaskIdsFilter($request->input('task_ids'));
        $filter->addClientIdsFilter($request->input('client_ids'));
        $filter->addBillableFilter($request->input('billable'));

        return $filter->get();
    }

    /**
     * Export time entries in organization
     *
     * @throws AuthorizationException|PdfRendererIsNotConfiguredException|FeatureIsNotAvailableInFreePlanApiException
     *
     * @operationId exportTimeEntries
     */
    public function indexExport(Organization $organization, TimeEntryIndexExportRequest $request, TimeEntryAggregationService $timeEntryAggregationService): JsonResponse
    {
        /** @var Member|null $member */
        $member = $request->has('member_id') ? Member::query()->findOrFail($request->input('member_id')) : null;
        if ($member !== null && $member->user_id === Auth::id()) {
            $this->checkPermission($organization, 'time-entries:view:own');
        } else {
            $this->checkPermission($organization, 'time-entries:view:all');
        }
        $debug = $request->getDebug();
        $format = $request->getFormatValue();
        if ($format === ExportFormat::PDF && ! $this->canAccessPremiumFeatures($organization)) {
            throw new FeatureIsNotAvailableInFreePlanApiException;
        }
        $user = $this->user();
        $timezone = $user->timezone;
        $showBillableRate = $this->member($organization)->role !== Role::Employee->value || $organization->employees_can_see_billable_rates;

        $timeEntriesQuery = $this->getTimeEntriesQuery($organization, $request, $member);
        $timeEntriesQuery->with([
            'task',
            'client',
            'project',
            'user',
            'tagsRelation',
        ]);
        $filename = 'time-entries-export-'.now()->format('Y-m-d_H-i-s').'.'.$format->getFileExtension();
        $folderPath = 'exports';
        $path = $folderPath.'/'.$filename;
        if ($format === ExportFormat::CSV) {
            $export = new TimeEntriesDetailedCsvExport(config('filesystems.private'), $folderPath, $filename, $timeEntriesQuery, 1000, $timezone);
            $export->export();
        } elseif ($format === ExportFormat::PDF) {
            if (config('services.gotenberg.url') === null && ! $debug) {
                throw new PdfRendererIsNotConfiguredException;
            }
            $viewFile = file_get_contents(resource_path('views/reports/time-entry-index/pdf.blade.php'));
            if ($viewFile === false) {
                throw new \LogicException('View file not found');
            }
            $aggregatedData = $timeEntryAggregationService->getAggregatedTimeEntries(
                $timeEntriesQuery->clone()->reorder()->withOnly([]),
                null,
                null,
                $user->timezone,
                $user->week_start,
                false,
                null,
                null,
                $showBillableRate
            );
            $html = Blade::render($viewFile, [
                'timeEntries' => $timeEntriesQuery->get(),
                'aggregatedData' => $aggregatedData,
                'timezone' => $timezone,
                'currency' => $organization->currency,
                'start' => $request->getStart()->timezone($timezone),
                'end' => $request->getEnd()->timezone($timezone),
            ]);
            $footerViewFile = file_get_contents(resource_path('views/reports/time-entry-index/pdf-footer.blade.php'));
            if ($footerViewFile === false) {
                throw new \LogicException('View file not found');
            }
            $footerHtml = Blade::render($footerViewFile);
            if ($debug) {
                return response()->json([
                    'html' => $html,
                    'footer_html' => $footerHtml,
                ]);
            }

            $client = new Client([
                'auth' => config('services.gotenberg.basic_auth_username') !== null && config('services.gotenberg.basic_auth_password') !== null ? [
                    config('services.gotenberg.basic_auth_username'),
                    config('services.gotenberg.basic_auth_password'),
                ] : null,
            ]);
            $request = Gotenberg::chromium(config('services.gotenberg.url'))
                ->pdf()
                ->assets(
                    Stream::path(resource_path('pdf/Outfit-VariableFont_wght.ttf'), 'outfit.ttf'),
                )
                ->margins(0.39, 0.78, 0.39, 0.39)
                ->paperSize('8.27', '11.7') // A4
                ->footer(Stream::string('footer', $footerHtml))
                ->html(Stream::string('body', $html));
            $tempFolder = TemporaryDirectory::make();
            $filenameTemp = Gotenberg::save($request, $tempFolder->path(), $client);
            Storage::disk(config('filesystems.private'))
                ->putFileAs($folderPath, new File($tempFolder->path($filenameTemp)), $filename);
        } else {
            Excel::store(
                new TimeEntriesDetailedExport($timeEntriesQuery, $format, $timezone),
                $path,
                config('filesystems.private'),
                $format->getExportPackageType(),
                [
                    'visibility' => 'private',
                ]
            );
        }

        return response()->json([
            'download_url' => Storage::disk(config('filesystems.private'))
                ->temporaryUrl($path, now()->addMinutes(5)),
        ]);
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
     *          grouped_type: string|null,
     *          grouped_data: null|array<array{
     *              key: string|null,
     *              seconds: int,
     *              cost: int|null,
     *              grouped_type: string|null,
     *              grouped_data: null|array<array{
     *                  key: string|null,
     *                  seconds: int,
     *                  cost: int|null,
     *                  grouped_type: null,
     *                  grouped_data: null
     *              }>
     *          }>,
     *          seconds: int,
     *          cost: int|null
     *      }
     * }
     *
     * @throws AuthorizationException
     */
    public function aggregate(Organization $organization, TimeEntryAggregateRequest $request, TimeEntryAggregationService $timeEntryAggregationService): array
    {
        /** @var Member|null $member */
        $member = $request->has('member_id') ? Member::query()->findOrFail($request->input('member_id')) : null;
        if ($member !== null && $member->user_id === Auth::id()) {
            $this->checkPermission($organization, 'time-entries:view:own');
        } else {
            $this->checkPermission($organization, 'time-entries:view:all');
        }
        $user = $this->user();
        $showBillableRate = $this->member($organization)->role !== Role::Employee->value || $organization->employees_can_see_billable_rates;

        $group1Type = $request->getGroup();
        $group2Type = $request->getSubGroup();
        $timeEntriesAggregateQuery = $this->getTimeEntriesAggregateQuery($organization, $request, $member);

        $aggregatedData = $timeEntryAggregationService->getAggregatedTimeEntries(
            $timeEntriesAggregateQuery,
            $group1Type,
            $group2Type,
            $user->timezone,
            $user->week_start,
            $request->getFillGapsInTimeGroups(),
            $request->getStart(),
            $request->getEnd(),
            $showBillableRate
        );

        return [
            'data' => $aggregatedData,
        ];
    }

    /**
     * Export aggregated time entries in organization
     *
     * @operationId exportAggregatedTimeEntries
     *
     * @throws AuthorizationException
     * @throws PdfRendererIsNotConfiguredException
     * @throws GotenbergApiErrored
     * @throws NoOutputFileInResponse
     * @throws FeatureIsNotAvailableInFreePlanApiException
     */
    public function aggregateExport(Organization $organization, TimeEntryAggregateExportRequest $request, TimeEntryAggregationService $timeEntryAggregationService): JsonResponse
    {
        /** @var Member|null $member */
        $member = $request->has('member_id') ? Member::query()->findOrFail($request->input('member_id')) : null;
        if ($member !== null && $member->user_id === Auth::id()) {
            $this->checkPermission($organization, 'time-entries:view:own');
        } else {
            $this->checkPermission($organization, 'time-entries:view:all');
        }
        $format = $request->getFormatValue();
        if ($format === ExportFormat::PDF && ! $this->canAccessPremiumFeatures($organization)) {
            throw new FeatureIsNotAvailableInFreePlanApiException;
        }
        $debug = $request->getDebug();
        $user = $this->user();
        $showBillableRate = $this->member($organization)->role !== Role::Employee->value || $organization->employees_can_see_billable_rates;

        $group = $request->getGroup();
        $subGroup = $request->getSubGroup();
        $timeEntriesAggregateQuery = $this->getTimeEntriesAggregateQuery($organization, $request, $member);

        $aggregatedData = $timeEntryAggregationService->getAggregatedTimeEntriesWithDescriptions(
            $timeEntriesAggregateQuery->clone(),
            $group,
            $subGroup,
            $user->timezone,
            $user->week_start,
            false,
            $request->getStart(),
            $request->getEnd(),
            $showBillableRate
        );
        $dataHistoryChart = $timeEntryAggregationService->getAggregatedTimeEntries(
            $timeEntriesAggregateQuery->clone(),
            $request->getHistoryGroup(),
            null,
            $user->timezone,
            $user->week_start,
            true,
            $request->getStart(),
            $request->getEnd(),
            $showBillableRate
        );
        $currency = $organization->currency;
        $timezone = app(TimezoneService::class)->getTimezoneFromUser($this->user());

        $filename = 'time-entries-report-'.now()->format('Y-m-d_H-i-s').'.'.$format->getFileExtension();
        $folderPath = 'exports';
        $path = $folderPath.'/'.$filename;

        if ($format === ExportFormat::PDF) {
            if (config('services.gotenberg.url') === null && ! $debug) {
                throw new PdfRendererIsNotConfiguredException;
            }
            $client = new Client([
                'auth' => config('services.gotenberg.basic_auth_username') !== null && config('services.gotenberg.basic_auth_password') !== null ? [
                    config('services.gotenberg.basic_auth_username'),
                    config('services.gotenberg.basic_auth_password'),
                ] : null,
            ]);
            $viewFile = file_get_contents(resource_path('views/reports/time-entry-aggregate/pdf.blade.php'));
            if ($viewFile === false) {
                throw new \LogicException('View file not found');
            }
            $html = Blade::render($viewFile, [
                'aggregatedData' => $aggregatedData,
                'dataHistoryChart' => $dataHistoryChart,
                'currency' => $currency,
                'group' => $group,
                'subGroup' => $subGroup,
                'timezone' => $timezone,
                'start' => $request->getStart()->timezone($timezone),
                'end' => $request->getEnd()->timezone($timezone),
                'debug' => $debug,
            ]);
            $footerViewFile = file_get_contents(resource_path('views/reports/time-entry-aggregate/pdf-footer.blade.php'));
            if ($footerViewFile === false) {
                throw new \LogicException('View file not found');
            }
            $footerHtml = Blade::render($footerViewFile);
            if ($debug) {
                return response()->json([
                    'html' => $html,
                    'footer_html' => $footerHtml,
                ]);
            }
            $request = Gotenberg::chromium(config('services.gotenberg.url'))
                ->pdf()
                ->waitForExpression("window.status === 'ready'")
                ->margins(0.39, 0.78, 0.39, 0.39)
                ->paperSize('8.27', '11.7') // A4
                ->footer(Stream::string('footer', $footerHtml))
                ->assets(Stream::path(resource_path('pdf/echarts.min.js'), 'echarts.min.js'),
                    Stream::path(resource_path('pdf/Outfit-VariableFont_wght.ttf'), 'outfit.ttf'),
                )
                ->html(Stream::string('body', $html));
            $tempFolder = TemporaryDirectory::make();
            $filenameTemp = Gotenberg::save($request, $tempFolder->path(), $client);
            Storage::disk(config('filesystems.private'))
                ->putFileAs($folderPath, new File($tempFolder->path($filenameTemp)), $filename);
        } else {
            Excel::store(
                new TimeEntriesReportExport($aggregatedData, $format, $currency, $group, $subGroup),
                $path,
                config('filesystems.private'),
                $format->getExportPackageType(),
                [
                    'visibility' => 'private',
                ]
            );
        }

        return response()->json([
            'download_url' => Storage::disk(config('filesystems.private'))
                ->temporaryUrl($path, now()->addMinutes(5)),
        ]);
    }

    /**
     * @return Builder<TimeEntry>
     */
    private function getTimeEntriesAggregateQuery(Organization $organization, TimeEntryAggregateRequest|TimeEntryAggregateExportRequest $request, ?Member $member): Builder
    {
        $timeEntriesQuery = TimeEntry::query()
            ->whereBelongsTo($organization, 'organization');

        $filter = new TimeEntryFilter($timeEntriesQuery);
        $filter->addEndFilter($request->input('end'));
        $filter->addStartFilter($request->input('start'));
        $filter->addActiveFilter($request->input('active'));
        $filter->addMemberIdFilter($member);
        $filter->addMemberIdsFilter($request->input('member_ids'));
        $filter->addProjectIdsFilter($request->input('project_ids'));
        $filter->addTagIdsFilter($request->input('tag_ids'));
        $filter->addTaskIdsFilter($request->input('task_ids'));
        $filter->addClientIdsFilter($request->input('client_ids'));
        $filter->addBillableFilter($request->input('billable'));

        return $filter->get();
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
        $member = Member::query()->findOrFail($request->input('member_id'));
        if ($member->user_id === Auth::id()) {
            $this->checkPermission($organization, 'time-entries:create:own');
        } else {
            $this->checkPermission($organization, 'time-entries:create:all');
        }

        if ($request->input('end') === null && TimeEntry::query()->whereBelongsTo($member, 'member')->where('end', null)->exists()) {
            throw new TimeEntryStillRunningApiException;
        }

        $project = $request->input('project_id') !== null ? Project::findOrFail((string) $request->input('project_id')) : null;
        $client = $project?->client;
        $task = $request->input('task_id') !== null ? $project->tasks()->findOrFail((string) $request->input('task_id')) : null;

        if ($project !== null) {
            RecalculateSpentTimeForProject::dispatch($project);
        }
        if ($task !== null) {
            RecalculateSpentTimeForTask::dispatch($task);
        }

        $timeEntry = new TimeEntry;
        $timeEntry->fill($request->validated());
        $timeEntry->client()->associate($client);
        $timeEntry->user_id = $member->user_id;
        $timeEntry->description = $request->input('description') ?? '';
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
        $member = $request->has('member_id') ? Member::query()->findOrFail($request->input('member_id')) : null;
        if ($timeEntry->member->user_id === Auth::id() && ($member === null || $member->user_id === Auth::id())) {
            $this->checkPermission($organization, 'time-entries:update:own');
        } else {
            $this->checkPermission($organization, 'time-entries:update:all');
        }

        if ($timeEntry->end !== null && $request->has('end') && $request->input('end') === null) {
            throw new TimeEntryCanNotBeRestartedApiException;
        }

        $oldProject = $timeEntry->project;
        $oldTask = $timeEntry->task;

        $project = null;
        if ($request->has('project_id')) {
            $project = $request->input('project_id') !== null ? Project::findOrFail((string) $request->input('project_id')) : null;
            $client = $project?->client;
            $timeEntry->client()->associate($client);
        }
        $task = null;
        if ($request->has('task_id')) {
            $task = $request->input('task_id') !== null ? Task::findOrFail((string) $request->input('task_id')) : null;
        }

        $timeEntry->fill($request->validated());
        $timeEntry->description = $request->input('description', $timeEntry->description) ?? '';
        $timeEntry->setComputedAttributeValue('billable_rate');
        $timeEntry->save();

        if ($oldProject !== null) {
            RecalculateSpentTimeForProject::dispatch($oldProject);
        }
        if ($oldTask !== null) {
            RecalculateSpentTimeForTask::dispatch($oldTask);
        }
        if ($project !== null && ($oldProject === null || $project->isNot($oldProject))) {
            RecalculateSpentTimeForProject::dispatch($project);
        }
        if ($task !== null && ($oldTask === null || $task->isNot($oldTask))) {
            RecalculateSpentTimeForTask::dispatch($task);
        }

        return new TimeEntryResource($timeEntry);
    }

    /**
     * Update multiple time entries
     *
     * @operationId updateMultipleTimeEntries
     *
     * @throws AuthorizationException
     */
    public function updateMultiple(Organization $organization, TimeEntryUpdateMultipleRequest $request): JsonResponse
    {
        $this->checkAnyPermission($organization, ['time-entries:update:all', 'time-entries:update:own']);
        $canAccessAll = $this->hasPermission($organization, 'time-entries:update:all');

        $ids = $request->validated('ids');

        $timeEntries = TimeEntry::query()
            ->whereBelongsTo($organization, 'organization')
            ->with([
                'project',
                'task',
            ])
            ->whereIn('id', $ids)
            ->get();

        $changes = $request->validated('changes');

        if ($request->has('changes.description')) {
            $changes['description'] = $request->input('changes.description') ?? '';
        }

        if (isset($changes['member_id']) && ! $canAccessAll && $this->member($organization)->getKey() !== $changes['member_id']) {
            throw new AuthorizationException;
        }

        $project = null;
        $client = null;
        $overwriteClient = false;
        if ($request->has('changes.project_id')) {
            $project = $request->input('changes.project_id') !== null ? Project::findOrFail((string) $request->input('changes.project_id')) : null;
            $client = $project?->client;
            $overwriteClient = true;
        }

        $task = null;
        if ($request->has('changes.task_id')) {
            $task = $request->input('changes.task_id') !== null ? Task::findOrFail((string) $request->input('changes.task_id')) : null;
        }

        $success = new Collection;
        $error = new Collection;

        foreach ($ids as $id) {
            /** @var TimeEntry|null $timeEntry */
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
            $oldProject = $timeEntry->project;
            $oldTask = $timeEntry->task;

            $timeEntry->fill($changes);
            // If project is changed, but task is not, we remove the old task from the time entry
            if ($oldProject !== null && $project !== null && $oldProject->isNot($project) && $task === null) {
                $timeEntry->task()->disassociate();
            }
            if ($overwriteClient) {
                $timeEntry->client()->associate($client);
            }
            $timeEntry->setComputedAttributeValue('billable_rate');
            $timeEntry->save();
            if ($oldTask !== null) {
                RecalculateSpentTimeForTask::dispatch($oldTask);
            }
            if ($oldProject !== null) {
                RecalculateSpentTimeForProject::dispatch($oldProject);
            }
            if ($project !== null && ($oldProject === null || $project->isNot($oldProject))) {
                RecalculateSpentTimeForProject::dispatch($project);
            }
            if ($task !== null && ($oldTask === null || $task->isNot($oldTask))) {
                RecalculateSpentTimeForTask::dispatch($task);
            }

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

        $project = $timeEntry->project;
        $task = $timeEntry->task;

        $timeEntry->delete();

        if ($project !== null) {
            RecalculateSpentTimeForProject::dispatch($project);
        }
        if ($task !== null) {
            RecalculateSpentTimeForTask::dispatch($task);
        }

        return response()
            ->json(null, 204);
    }

    /**
     * Delete multiple time entries
     *
     * @throws AuthorizationException
     *
     * @operationId deleteTimeEntries
     */
    public function destroyMultiple(Organization $organization, TimeEntryDestroyMultipleRequest $request): JsonResponse
    {
        $this->checkAnyPermission($organization, ['time-entries:delete:all', 'time-entries:delete:own']);
        $canDeleteAll = $this->hasPermission($organization, 'time-entries:delete:all');

        $ids = $request->validated('ids');
        $timeEntries = TimeEntry::query()
            ->whereBelongsTo($organization, 'organization')
            ->with([
                'project',
                'task',
            ])
            ->whereIn('id', $ids)
            ->get();

        $success = new Collection;
        $error = new Collection;

        foreach ($ids as $id) {
            /** @var TimeEntry|null $timeEntry */
            $timeEntry = $timeEntries->firstWhere('id', $id);
            if ($timeEntry === null) {
                // Note: ID wrong or time entry in different organization
                $error->push($id);

                continue;
            }

            if (! $canDeleteAll && $timeEntry->user_id !== Auth::id()) {
                $error->push($id);

                continue;

            }

            $project = $timeEntry->project;
            $task = $timeEntry->task;

            $timeEntry->delete();

            if ($project !== null) {
                RecalculateSpentTimeForProject::dispatch($project);
            }
            if ($task !== null) {
                RecalculateSpentTimeForTask::dispatch($task);
            }
            $success->push($id);
        }

        return response()->json([
            'success' => $success->toArray(),
            'error' => $error->toArray(),
        ]);
    }
}
