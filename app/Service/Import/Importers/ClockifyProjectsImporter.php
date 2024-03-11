<?php

declare(strict_types=1);

namespace App\Service\Import\Importers;

use App\Models\Client;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Service\ColorService;
use App\Service\Import\ImportDatabaseHelper;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use League\Csv\Exception as CsvException;
use League\Csv\Reader;

class ClockifyProjectsImporter implements ImporterContract
{
    private Organization $organization;

    /**
     * @var ImportDatabaseHelper<Project>
     */
    private ImportDatabaseHelper $projectImportHelper;

    /**
     * @var ImportDatabaseHelper<Client>
     */
    private ImportDatabaseHelper $clientImportHelper;

    /**
     * @var ImportDatabaseHelper<Task>
     */
    private ImportDatabaseHelper $taskImportHelper;

    #[\Override]
    public function init(Organization $organization): void
    {
        $this->organization = $organization;
        $this->projectImportHelper = new ImportDatabaseHelper(Project::class, ['name', 'organization_id'], true, function (Builder $builder) {
            return $builder->where('organization_id', $this->organization->id);
        });
        $this->clientImportHelper = new ImportDatabaseHelper(Client::class, ['name', 'organization_id'], true, function (Builder $builder) {
            return $builder->where('organization_id', $this->organization->id);
        });
        $this->taskImportHelper = new ImportDatabaseHelper(Task::class, ['name', 'project_id', 'organization_id'], true, function (Builder $builder) {
            return $builder->where('organization_id', $this->organization->id);
        });
    }

    /**
     * @throws ImportException
     */
    #[\Override]
    public function importData(string $data): void
    {
        try {
            $colorService = app(ColorService::class);
            $reader = Reader::createFromString($data);
            $reader->setHeaderOffset(0);
            $reader->setDelimiter(',');
            $header = $reader->getHeader();
            $this->validateHeader($header);
            $records = $reader->getRecords();
            foreach ($records as $record) {
                $clientId = null;
                if ($record['Client'] !== '') {
                    $clientId = $this->clientImportHelper->getKey([
                        'name' => $record['Client'],
                        'organization_id' => $this->organization->id,
                    ]);
                }
                $projectId = null;
                if ($record['Name'] !== '') {
                    $projectId = $this->projectImportHelper->getKey([
                        'name' => $record['Name'],
                        'organization_id' => $this->organization->id,
                    ], [
                        'client_id' => $clientId,
                        'color' => $colorService->getRandomColor(),
                    ]);
                }

                if ($record['Tasks'] !== '') {
                    $tasks = explode(', ', $record['Tasks']);
                    foreach ($tasks as $task) {
                        if (strlen($task) > 255) {
                            throw new ImportException('Task is too long');
                        }
                        $taskId = $this->taskImportHelper->getKey([
                            'name' => $task,
                            'project_id' => $projectId,
                            'organization_id' => $this->organization->id,
                        ]);
                    }
                }
            }
        } catch (ImportException $exception) {
            throw $exception;
        } catch (CsvException $exception) {
            throw new ImportException('Invalid CSV data');
        } catch (Exception $exception) {
            report($exception);
            throw new ImportException('Unknown error');
        }
    }

    /**
     * @param  array<string>  $header
     *
     * @throws ImportException
     */
    private function validateHeader(array $header): void
    {
        $requiredFields = [
            'Name',
            'Client',
            'Status',
            'Visibility',
            'Billability',
            'Tasks',
        ];
        foreach ($requiredFields as $requiredField) {
            if (! in_array($requiredField, $header, true)) {
                throw new ImportException('Invalid CSV header, missing field: '.$requiredField);
            }
        }
    }

    #[\Override]
    public function getReport(): ReportDto
    {
        return new ReportDto(
            clientsCreated: $this->clientImportHelper->getCreatedCount(),
            projectsCreated: $this->projectImportHelper->getCreatedCount(),
            tasksCreated: $this->taskImportHelper->getCreatedCount(),
            timeEntriesCreated: 0,
            tagsCreated: 0,
            usersCreated: 0,
        );
    }
}
