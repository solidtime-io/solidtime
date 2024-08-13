<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\TimeEntryAggregationType;
use App\Http\Requests\V1\Report\ReportStoreRequest;
use App\Http\Requests\V1\Report\ReportUpdateRequest;
use App\Http\Resources\V1\Report\DetailedReportResource;
use App\Http\Resources\V1\Report\ReportCollection;
use App\Models\Organization;
use App\Models\Report;
use App\Service\Dto\ReportPropertiesDto;
use App\Service\ReportService;
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
    public function store(Organization $organization, ReportStoreRequest $request): DetailedReportResource
    {
        $this->checkPermission($organization, 'reports:create');

        $report = new Report;
        $report->name = $request->getName();
        $report->description = $request->getDescription();
        $isPublic = $request->getIsPublic();
        $report->is_public = $isPublic;
        $properties = new ReportPropertiesDto;
        $properties->group = TimeEntryAggregationType::from($request->input('properties.group'));
        $properties->subGroup = TimeEntryAggregationType::from($request->input('properties.sub_group'));
        $report->properties = $properties;
        if ($isPublic) {
            $report->share_secret = app(ReportService::class)->generateSecret();
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
    public function update(Organization $organization, Report $report, ReportUpdateRequest $request): DetailedReportResource
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
                $report->share_secret = app(ReportService::class)->generateSecret();
                $report->public_until = $request->getPublicUntil();
            } else {
                $report->share_secret = null;
                $report->public_until = null;
            }
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
