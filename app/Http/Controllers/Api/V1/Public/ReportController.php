<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Public;

use App\Enums\TimeEntryAggregationType;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Resources\V1\Report\DetailedWithDataReportResource;
use App\Models\Report;
use App\Models\TimeEntry;
use App\Service\Dto\ReportPropertiesDto;
use App\Service\TimeEntryAggregationService;
use App\Service\TimeEntryFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Get report by a share secret
     *
     * This endpoint is public and does not require authentication. The report must be public and not expired.
     * The report is considered expired if the `public_until` field is set and the date is in the past.
     * The report is considered public if the `is_public` field is set to `true`.
     *
     * @operationId getPublicReport
     */
    public function show(Request $request, TimeEntryAggregationService $timeEntryAggregationService): DetailedWithDataReportResource
    {
        $shareSecret = $request->header('X-Api-Key');
        if (! is_string($shareSecret)) {
            throw new ModelNotFoundException;
        }

        $report = Report::query()
            ->with([
                'organization',
            ])
            ->where('share_secret', '=', $shareSecret)
            ->where('is_public', '=', true)
            ->where(function (Builder $builder): void {
                /** @var Builder<Report> $builder */
                $builder->whereNull('public_until')
                    ->orWhere('public_until', '>', now());
            })
            ->firstOrFail();
        /** @var ReportPropertiesDto $properties */
        $properties = $report->properties;

        $timeEntriesQuery = TimeEntry::query()
            ->whereBelongsTo($report->organization, 'organization');

        $filter = new TimeEntryFilter($timeEntriesQuery);
        $filter->addStart($properties->start);
        $filter->addEnd($properties->end);
        $filter->addActive($properties->active);
        $filter->addBillable($properties->billable);
        $filter->addMemberIdsFilter($properties->memberIds?->toArray());
        $filter->addProjectIdsFilter($properties->projectIds?->toArray());
        $filter->addTagIdsFilter($properties->tagIds?->toArray());
        $filter->addTaskIdsFilter($properties->taskIds?->toArray());
        $filter->addClientIdsFilter($properties->clientIds?->toArray());
        $timeEntriesQuery = $filter->get();

        $data = $timeEntryAggregationService->getAggregatedTimeEntriesWithDescriptions(
            $timeEntriesQuery->clone(),
            $report->properties->group,
            $report->properties->subGroup,
            $report->properties->timezone,
            $report->properties->weekStart,
            false,
            $report->properties->start,
            $report->properties->end,
        );
        $historyData = $timeEntryAggregationService->getAggregatedTimeEntriesWithDescriptions(
            $timeEntriesQuery->clone(),
            TimeEntryAggregationType::fromInterval($report->properties->historyGroup),
            null,
            $report->properties->timezone,
            $report->properties->weekStart,
            true,
            $report->properties->start,
            $report->properties->end,
        );

        return new DetailedWithDataReportResource($report, $data, $historyData);
    }
}
