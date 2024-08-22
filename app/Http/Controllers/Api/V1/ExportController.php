<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\Organization;
use App\Service\Export\ExportException;
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
     * @throws AuthorizationException
     * @throws ExportException
     *
     * @operationId exportOrganization
     */
    public function export(Organization $organization, ExportService $exportService): JsonResponse
    {
        $this->checkPermission($organization, 'export');

        $filepath = $exportService->export($organization);
        $downloadUrl = Storage::disk(config('filesystems.private'))
            ->temporaryUrl($filepath, Carbon::now()->addMinutes(10));

        return new JsonResponse([
            'success' => true,
            'download_url' => $downloadUrl,
        ], 200);
    }
}
