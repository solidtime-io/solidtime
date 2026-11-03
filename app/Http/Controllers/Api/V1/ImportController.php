<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\Import\ImportIndexRequest;
use App\Http\Requests\V1\Import\ImportRequest;
use App\Http\Resources\V1\Import\ImportCollection;
use App\Http\Resources\V1\Import\ImportResource;
use App\Models\Import;
use App\Models\Organization;
use App\Service\Import\Importers\ImporterContract;
use App\Service\Import\Importers\ImporterProvider;
use App\Service\Import\Importers\ImportException;
use App\Service\Import\ImportService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;

class ImportController extends Controller
{
    /**
     * Get information about available importers
     *
     * @operationId getImporters
     *
     * @throws AuthorizationException
     *
     * @response array{data: array<array{ key: string, name: string, description: string }>}
     */
    public function index(Organization $organization, ImporterProvider $importerProvider): JsonResponse
    {
        $this->checkPermission($organization, 'import');

        $importers = $importerProvider->getImporters();

        /** @var array<array{ key: string, name: string, description: string }> $importersResponse */
        $importersResponse = [];

        foreach ($importers as $key => $importerClass) {
            /** @var ImporterContract $importer */
            $importer = new $importerClass;
            $importersResponse[] = [
                'key' => $key,
                'name' => $importer->getName(),
                'description' => $importer->getDescription(),
            ];
        }

        return new JsonResponse([
            'data' => $importersResponse,
        ], 200);
    }

    /**
     * Import data into the organization
     *
     * The import is processed in the background. Use the returned import ID to poll the status of the import.
     *
     * @throws AuthorizationException
     *
     * @operationId importData
     */
    public function import(Organization $organization, ImportRequest $request, ImportService $importService): JsonResponse|ImportResource
    {
        $this->checkPermission($organization, 'import');

        try {
            $importData = base64_decode($request->input('data'), true);
            if ($importData === false) {
                return new JsonResponse([
                    'message' => 'Invalid base64 encoded data',
                ], 400);
            }

            $user = $this->user();
            $import = $importService->createFileImport(
                $organization,
                $user,
                $request->input('type'),
                $importData,
                $user->timezone
            );

            return (new ImportResource($import->refresh()))
                ->response()
                ->setStatusCode(202);
        } catch (ImportException $exception) {
            return new JsonResponse([
                'message' => $exception->getMessage(),
            ], 400);
        }
    }

    /**
     * Get imports
     *
     * @throws AuthorizationException
     *
     * @operationId getImports
     */
    public function indexImports(Organization $organization, ImportIndexRequest $request): ImportCollection
    {
        $this->checkPermission($organization, 'import');

        $imports = Import::query()
            ->whereBelongsTo($organization, 'organization')
            ->orderBy('created_at', 'desc')
            ->orderBy('id')
            ->paginate(config('app.pagination_per_page_default'));

        return new ImportCollection($imports);
    }

    /**
     * Get import
     *
     * @throws AuthorizationException
     *
     * @operationId getImport
     */
    public function show(Organization $organization, Import $import): ImportResource
    {
        $this->checkPermission($organization, 'import');
        if ($import->organization_id !== $organization->getKey()) {
            throw new AuthorizationException('Import does not belong to organization');
        }

        return new ImportResource($import);
    }
}
