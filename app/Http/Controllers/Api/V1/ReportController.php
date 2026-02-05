<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\Weekday;
use App\Http\Requests\V1\Report\ReportStoreRequest;
use App\Http\Requests\V1\Report\ReportUpdateRequest;
use App\Http\Resources\V1\Report\DetailedReportResource;
use App\Http\Resources\V1\Report\ReportCollection;
use App\Http\Resources\V1\Report\ReportResource;
use App\Models\Organization;
use App\Models\Report;
use App\Service\Dto\ReportPropertiesDto;
use App\Service\ReportService;
use App\Service\TimezoneService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    protected function checkPermission(Organization $organization, string $permission, ?Report $report = null): void
    {
        parent::checkPermission($organization, $permission);
        if ($report !== null && $report->organization_id !== $organization->id) {
            throw new AuthorizationException('Report does not belong to organization');
        }
    }

    /**
     * Get reports
     *
     * @return ReportCollection<ReportResource>
     *
     * @throws AuthorizationException
     *
     * @operationId getReports
     */
    public function index(Organization $organization): ReportCollection
    {
        $this->checkPermission($organization, 'reports:view');

        $reports = Report::query()
            ->orderBy('created_at', 'desc')
            ->whereBelongsTo($organization, 'organization')
            ->paginate(config('app.pagination_per_page_default'));

        return new ReportCollection($reports);
    }

    /**
     * Get report
     *
     * @throws AuthorizationException
     *
     * @operationId getReport
     */
    public function show(Organization $organization, Report $report): DetailedReportResource
    {
        $this->checkPermission($organization, 'reports:view', $report);

        return new DetailedReportResource($report);
    }

    /**
     * Create report
     *
     * @throws AuthorizationException
     *
     * @operationId createReport
     */
    public function store(Organization $organization, ReportStoreRequest $request, TimezoneService $timezoneService, ReportService $reportService): DetailedReportResource
    {
        $this->checkPermission($organization, 'reports:create');
        $user = $this->user();

        $report = new Report;
        $report->name = $request->getName();
        $report->description = $request->getDescription();
        $isPublic = $request->getIsPublic();
        $report->is_public = $isPublic;
        $properties = new ReportPropertiesDto;
        $properties->group = $request->getPropertyGroup();
        $properties->subGroup = $request->getPropertySubGroup();
        $properties->historyGroup = $request->getPropertyHistoryGroup();
        $properties->start = $request->getPropertyStart();
        $properties->end = $request->getPropertyEnd();
        $properties->active = $request->getPropertyActive();
        $properties->setMemberIds($request->input('properties.member_ids', null));
        $properties->billable = $request->getPropertyBillable();
        $properties->setClientIds($request->input('properties.client_ids', null));
        $properties->setProjectIds($request->input('properties.project_ids', null));
        $properties->setTagIds($request->input('properties.tag_ids', null));
        $properties->setTaskIds($request->input('properties.task_ids', null));
        $properties->weekStart = $request->has('properties.week_start') ? Weekday::from($request->input('properties.week_start')) : $user->week_start;
        $timezone = $user->timezone;
        if ($request->has('properties.timezone')) {
            if ($timezoneService->isValid($request->input('properties.timezone'))) {
                $timezone = $request->input('properties.timezone');
            }
            if ($timezoneService->mapLegacyTimezone($request->input('properties.timezone')) !== null) {
                $timezone = $timezoneService->mapLegacyTimezone($request->input('properties.timezone'));
            }
        }
        $properties->timezone = $timezone;
        $properties->roundingType = $request->getPropertyRoundingType();
        $properties->roundingMinutes = $request->getPropertyRoundingMinutes();
        $report->properties = $properties;
        if ($isPublic) {
            $report->share_secret = $reportService->generateSecret();
            $report->public_until = $request->getPublicUntil();
        } else {
            $report->share_secret = null;
            $report->public_until = null;
        }
        $report->organization()->associate($organization);
        $report->save();

        return new DetailedReportResource($report);
    }

    /**
     * Update report
     *
     * @throws AuthorizationException
     *
     * @operationId updateReport
     */
    public function update(Organization $organization, Report $report, ReportUpdateRequest $request, ReportService $reportService): DetailedReportResource
    {
        $this->checkPermission($organization, 'reports:update', $report);

        if ($request->has('name')) {
            $report->name = $request->getName();
        }
        if ($request->has('description')) {
            $report->description = $request->getDescription();
        }
        if ($request->has('is_public') && $request->getIsPublic() !== $report->is_public) {
            $isPublic = $request->getIsPublic();
            $report->is_public = $isPublic;
            if ($isPublic) {
                $report->share_secret = $reportService->generateSecret();
                $report->public_until = $request->getPublicUntil();
            } else {
                $report->share_secret = null;
                $report->public_until = null;
            }
        } elseif ($report->is_public && $request->has('public_until')) {
            // Allow updating expiration date on already-public reports
            $report->public_until = $request->getPublicUntil();
        }
        $report->save();

        return new DetailedReportResource($report);
    }

    /**
     * Delete report
     *
     * @throws AuthorizationException
     *
     * @operationId deleteReport
     */
    public function destroy(Organization $organization, Report $report): JsonResponse
    {
        $this->checkPermission($organization, 'reports:delete', $report);

        $report->delete();

        return response()->json(null, 204);
    }
}
