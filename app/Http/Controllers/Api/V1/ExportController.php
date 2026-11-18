<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\ExportStatus;
use App\Exceptions\Api\ExportAlreadyInProgressApiException;
use App\Exceptions\Api\ExportFileNotAvailableApiException;
use App\Http\Requests\V1\Export\ExportIndexRequest;
use App\Http\Resources\V1\Export\ExportCollection;
use App\Http\Resources\V1\Export\ExportResource;
use App\Models\Export;
use App\Models\Organization;
use App\Service\Export\ExportService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class ExportController extends Controller
{
    /**
     * Export data of an organization
     *
     * The export is processed in the background. Use the returned export ID to poll the status of the export and
     * the download endpoint to get the download URL once the export is completed.
     *
     * @throws AuthorizationException
     * @throws ExportAlreadyInProgressApiException
     *
     * @operationId exportOrganization
     */
    public function export(Organization $organization, ExportService $exportService): JsonResponse
    {
        $this->checkPermission($organization, 'export');

        $export = $exportService->createOrganizationExport($organization, $this->user());

        return (new ExportResource($export->refresh()))
            ->response()
            ->setStatusCode(202);
    }

    /**
     * Get exports
     *
     * @throws AuthorizationException
     *
     * @operationId getExports
     */
    public function index(Organization $organization, ExportIndexRequest $request): ExportCollection
    {
        $this->checkPermission($organization, 'export');

        $exports = Export::query()
            ->whereBelongsTo($organization, 'organization')
            ->orderBy('created_at', 'desc')
            ->orderBy('id')
            ->paginate(config('app.pagination_per_page_default'));

        return new ExportCollection($exports);
    }

    /**
     * Get export
     *
     * @throws AuthorizationException
     *
     * @operationId getExport
     */
    public function show(Organization $organization, Export $export): ExportResource
    {
        $this->checkPermission($organization, 'export');
        $this->checkExportBelongsToOrganization($organization, $export);

        return new ExportResource($export);
    }

    /**
     * Get download URL of an export
     *
     * The download URL is only valid for a short time, request a new one for every download.
     *
     * @throws AuthorizationException
     * @throws ExportFileNotAvailableApiException
     *
     * @operationId downloadExport
     */
    public function download(Organization $organization, Export $export): JsonResponse
    {
        $this->checkPermission($organization, 'export');
        $this->checkExportBelongsToOrganization($organization, $export);

        if ($export->status !== ExportStatus::Completed || $export->file_path === null) {
            throw new ExportFileNotAvailableApiException;
        }

        $downloadUrl = Storage::disk(config('filesystems.private'))
            ->temporaryUrl($export->file_path, Carbon::now()->addMinutes(10), [
                'ResponseContentDisposition' => 'attachment; filename="'.$export->getDownloadFilename($this->user()->timezone).'"',
            ]);

        return new JsonResponse([
            /** @var string $download_url Temporary download URL of the export file, valid for 10 minutes */
            'download_url' => $downloadUrl,
        ], 200);
    }

    /**
     * @throws AuthorizationException
     */
    private function checkExportBelongsToOrganization(Organization $organization, Export $export): void
    {
        if ($export->organization_id !== $organization->getKey()) {
            throw new AuthorizationException('Export does not belong to organization');
        }
    }
}
