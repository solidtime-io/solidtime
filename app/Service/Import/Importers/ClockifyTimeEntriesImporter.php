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
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use League\Csv\Exception as CsvException;
use League\Csv\Reader;

class ClockifyTimeEntriesImporter implements ImporterContract
{
    private Organization $organization;

    /**
     * @var ImportDatabaseHelper<User>
     */
    private ImportDatabaseHelper $userImportHelper;

    /**
     * @var ImportDatabaseHelper<Project>
     */
    private ImportDatabaseHelper $projectImportHelper;

    /**
     * @var ImportDatabaseHelper<Tag>
     */
    private ImportDatabaseHelper $tagImportHelper;

    /**
     * @var ImportDatabaseHelper<Client>
     */
    private ImportDatabaseHelper $clientImportHelper;

    /**
     * @var ImportDatabaseHelper<Task>
     */
    private ImportDatabaseHelper $taskImportHelper;

    private int $timeEntriesCreated;

    #[\Override]
    public function init(Organization $organization): void
    {
        $this->organization = $organization;
        $this->userImportHelper = new ImportDatabaseHelper(User::class, ['email'], true, function (Builder $builder) {
            /** @var Builder<User> $builder */
            return $builder->belongsToOrganization($this->organization);
        }, function (User $user) {
            $user->organizations()->attach($this->organization, [
                'role' => 'placeholder',
            ]);
        });
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

    /**
     * @return array<string>
     *
     * @throws ImportException
     */
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
                $start = Carbon::createFromFormat('m/d/Y H:i:s A', $record['Start Date'].' '.$record['Start Time'], 'UTC');
                if ($start === false) {
                    throw new ImportException('Start date ("'.$record['Start Date'].'") or time ("'.$record['Start Time'].'") are invalid');
                }
                $timeEntry->start = $start;
                $end = Carbon::createFromFormat('m/d/Y H:i:s A', $record['End Date'].' '.$record['End Time'], 'UTC');
                if ($end === false) {
                    throw new ImportException('End date ("'.$record['End Date'].'") or time ("'.$record['End Time'].'") are invalid');
                }
                $timeEntry->end = $end;
                $timeEntry->save();
                $this->timeEntriesCreated++;
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
            'Project',
            'Client',
            'Description',
            'Task',
            'User',
            'Group',
            'Email',
            'Tags',
            'Billable',
            'Start Date',
            'Start Time',
            'End Date',
            'End Time',
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
