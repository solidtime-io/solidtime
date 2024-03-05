<?php

declare(strict_types=1);

namespace App\Service\Import\Importers;

use App\Models\Client;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use App\Service\ColorService;
use App\Service\Import\ImportDatabaseHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use League\Csv\Exception as CsvException;
use League\Csv\Reader;

class TogglTimeEntriesImporter implements ImporterContract
{
    private Organization $organization;

    private ImportDatabaseHelper $userImportHelper;

    private ImportDatabaseHelper $projectImportHelper;

    private ImportDatabaseHelper $tagImportHelper;

    private ImportDatabaseHelper $clientImportHelper;

    private ImportDatabaseHelper $taskImportHelper;

    private int $timeEntriesCreated;

    #[\Override]
    public function init(Organization $organization): void
    {
        $this->organization = $organization;
        $this->userImportHelper = new ImportDatabaseHelper(User::class, ['email'], true, function (Builder $builder) {
            return $builder->whereHas('organizations', function (Builder $builder): Builder {
                /** @var Builder<Organization> $builder */
                return $builder->whereKey($this->organization->getKey());
            });
        }, function (User $user) {
            $user->organizations()->attach([$this->organization->id]);
        });
        // TODO: user special after import
        $this->projectImportHelper = new ImportDatabaseHelper(Project::class, ['name', 'organization_id'], true, function (Builder $builder) {
            return $builder->where('organization_id', $this->organization->id);
        });
        $this->tagImportHelper = new ImportDatabaseHelper(Tag::class, ['name', 'organization_id'], true, function (Builder $builder) {
            return $builder->where('organization_id', $this->organization->id);
        });
        $this->clientImportHelper = new ImportDatabaseHelper(Client::class, ['name', 'organization_id'], true, function (Builder $builder) {
            return $builder->where('organization_id', $this->organization->id);
        });
        $this->taskImportHelper = new ImportDatabaseHelper(Task::class, ['name', 'project_id', 'organization_id'], true, function (Builder $builder) {
            return $builder->where('organization_id', $this->organization->id);
        });
        $this->timeEntriesCreated = 0;
    }

    private function getTags(string $tags): array
    {
        if (trim($tags) === '') {
            return [];
        }
        $tagsParsed = explode(', ', $tags);
        $tagIds = [];
        foreach ($tagsParsed as $tagParsed) {
            if (strlen($tagParsed) > 255) {
                throw new ImportException('Tag is too long');
            }
            $tagId = $this->tagImportHelper->getKey([
                'name' => $tagParsed,
                'organization_id' => $this->organization->id,
            ]);
            $tagIds[] = $tagId;
        }

        return $tagIds;
    }

    /**
     * @throws ImportException
     */
    #[\Override]
    public function importData(string $data, array $options): void
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
                $userId = $this->userImportHelper->getKey([
                    'email' => $record['Email'],
                ], [
                    'name' => $record['User'],
                    'is_placeholder' => true,
                ]);
                $clientId = null;
                if ($record['Client'] !== '') {
                    $clientId = $this->clientImportHelper->getKey([
                        'name' => $record['Client'],
                        'organization_id' => $this->organization->id,
                    ]);
                }
                $projectId = null;
                if ($record['Project'] !== '') {
                    $projectId = $this->projectImportHelper->getKey([
                        'name' => $record['Project'],
                        'organization_id' => $this->organization->id,
                    ], [
                        'client_id' => $clientId,
                        'color' => $colorService->getRandomColor(),
                    ]);
                }
                $taskId = null;
                if ($record['Task'] !== '') {
                    $taskId = $this->taskImportHelper->getKey([
                        'name' => $record['Task'],
                        'project_id' => $projectId,
                        'organization_id' => $this->organization->id,
                    ]);
                }
                $timeEntry = new TimeEntry();
                $timeEntry->user_id = $userId;
                $timeEntry->task_id = $taskId;
                $timeEntry->project_id = $projectId;
                $timeEntry->organization_id = $this->organization->id;
                $timeEntry->description = $record['Description'];
                if (! in_array($record['Billable'], ['Yes', 'No'], true)) {
                    throw new ImportException('Invalid billable value');
                }
                $timeEntry->billable = $record['Billable'] === 'Yes';
                $timeEntry->tags = $this->getTags($record['Tags']);
                $timeEntry->start = Carbon::createFromFormat('Y-m-d H:i:s', $record['Start date'].' '.$record['Start time'], 'UTC');
                $timeEntry->end = Carbon::createFromFormat('Y-m-d H:i:s', $record['End date'].' '.$record['End time'], 'UTC');
                $timeEntry->save();
                $this->timeEntriesCreated++;
            }
        } catch (CsvException $exception) {
            throw new ImportException('Invalid CSV data');
        }

    }

    private function validateHeader(array $header): void
    {
        $requiredFields = [
            'User',
            'Email',
            'Client',
            'Project',
            'Task',
            'Description',
            'Billable',
            'Start date',
            'Start time',
            'End date',
            'End time',
            'Tags',
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
            timeEntriesCreated: $this->timeEntriesCreated,
            tagsCreated: $this->tagImportHelper->getCreatedCount(),
            usersCreated: $this->userImportHelper->getCreatedCount(),
        );
    }
}
