<?php

declare(strict_types=1);

namespace App\Service\Import\Importers;

use App\Enums\Role;
use App\Jobs\RecalculateSpentTimeForProject;
use App\Jobs\RecalculateSpentTimeForTask;
use App\Models\TimeEntry;
use Carbon\Exceptions\InvalidFormatException;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use League\Csv\Reader;
use Override;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use ZipArchive;

class SolidtimeImporter extends DefaultImporter
{
    /**
     * @var array<string>
     */
    public const array SUPPORTED_VERSIONS = ['1.0'];

    /**
     * @throws ImportException
     */
    #[Override]
    public function importData(string $data, string $timezone): void
    {
        $temporaryDirectoryZip = null;
        $temporaryDirectory = null;
        try {
            $zip = new ZipArchive;
            $temporaryDirectoryZip = TemporaryDirectory::make();
            file_put_contents($temporaryDirectoryZip->path('import.zip'), $data);
            $res = $zip->open($temporaryDirectoryZip->path('import.zip'), ZipArchive::RDONLY);
            if ($res !== true) {
                throw new ImportException('Invalid ZIP, error code: '.$res);
            }
            $temporaryDirectory = TemporaryDirectory::make();
            $zip->extractTo($temporaryDirectory->path());
            $zip->close();

            if (! file_exists($temporaryDirectory->path('meta.json'))) {
                throw new ImportException('File "meta.json" missing in ZIP');
            }
            $metaFileContentRaw = file_get_contents($temporaryDirectory->path('meta.json'));
            if ($metaFileContentRaw === false) {
                throw new ImportException('File "meta.json" can not read');
            }
            $metaFileContent = json_decode($metaFileContentRaw);
            if ($metaFileContent === false || ! isset($metaFileContent->version) || ! in_array($metaFileContent->version, self::SUPPORTED_VERSIONS, true)) {
                throw new ImportException('Invalid version');
            }

            if (! file_exists($temporaryDirectory->path('clients.csv'))) {
                throw new ImportException('File "clients.csv" missing in ZIP');
            }
            $clientsReader = Reader::createFromPath($temporaryDirectory->path('clients.csv'));
            $clientsReader->setHeaderOffset(0);
            $clientsReader->setDelimiter(',');
            $clientsReader->setEnclosure('"');
            $clientsReader->setEscape('');

            if (! file_exists($temporaryDirectory->path('members.csv'))) {
                throw new ImportException('File "members.csv" missing in ZIP');
            }
            $membersReader = Reader::createFromPath($temporaryDirectory->path('members.csv'));
            $membersReader->setHeaderOffset(0);
            $membersReader->setDelimiter(',');
            $membersReader->setEnclosure('"');
            $membersReader->setEscape('');

            if (! file_exists($temporaryDirectory->path('organization_invitations.csv'))) {
                throw new ImportException('File "organization_invitations.csv" missing in ZIP');
            }
            $organizationInvitationsReader = Reader::createFromPath($temporaryDirectory->path('organization_invitations.csv'));
            $organizationInvitationsReader->setHeaderOffset(0);
            $organizationInvitationsReader->setDelimiter(',');
            $organizationInvitationsReader->setEnclosure('"');
            $organizationInvitationsReader->setEscape('');

            if (! file_exists($temporaryDirectory->path('project_members.csv'))) {
                throw new ImportException('File "project_members.csv" missing in ZIP');
            }
            $projectMembersReader = Reader::createFromPath($temporaryDirectory->path('project_members.csv'));
            $projectMembersReader->setHeaderOffset(0);
            $projectMembersReader->setDelimiter(',');
            $projectMembersReader->setEnclosure('"');
            $projectMembersReader->setEscape('');

            if (! file_exists($temporaryDirectory->path('projects.csv'))) {
                throw new ImportException('File "projects.csv" missing in ZIP');
            }
            $projectsReader = Reader::createFromPath($temporaryDirectory->path('projects.csv'));
            $projectsReader->setHeaderOffset(0);
            $projectsReader->setDelimiter(',');
            $projectsReader->setEnclosure('"');
            $projectsReader->setEscape('');

            if (! file_exists($temporaryDirectory->path('tags.csv'))) {
                throw new ImportException('File "tags.csv" missing in ZIP');
            }
            $tagsReader = Reader::createFromPath($temporaryDirectory->path('tags.csv'));
            $tagsReader->setHeaderOffset(0);
            $tagsReader->setDelimiter(',');
            $tagsReader->setEnclosure('"');
            $tagsReader->setEscape('');

            if (! file_exists($temporaryDirectory->path('tasks.csv'))) {
                throw new ImportException('File "tasks.csv" missing in ZIP');
            }
            $tasksReader = Reader::createFromPath($temporaryDirectory->path('tasks.csv'));
            $tasksReader->setHeaderOffset(0);
            $tasksReader->setDelimiter(',');
            $tasksReader->setEnclosure('"');
            $tasksReader->setEscape('');

            if (! file_exists($temporaryDirectory->path('time_entries.csv'))) {
                throw new ImportException('File "time_entries.csv" missing in ZIP');
            }
            $timeEntriesReader = Reader::createFromPath($temporaryDirectory->path('time_entries.csv'));
            $timeEntriesReader->setHeaderOffset(0);
            $timeEntriesReader->setDelimiter(',');
            $timeEntriesReader->setEnclosure('"');
            $timeEntriesReader->setEscape('');

            foreach ($clientsReader as $client) {
                $this->clientImportHelper->getKey([
                    'name' => $client['name'],
                    'organization_id' => $this->organization->id,
                ], [
                    'archived_at' => $client['archived_at'] !== '' ? Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $client['archived_at'], 'UTC') : null,
                ], $client['id']);
            }

            foreach ($tagsReader as $tag) {
                $this->tagImportHelper->getKey([
                    'name' => $tag['name'],
                    'organization_id' => $this->organization->id,
                ], [], $tag['id']);
            }

            foreach ($membersReader as $member) {
                $userId = $this->userImportHelper->getKey([
                    'email' => $member['email'],
                ], [
                    'name' => $member['name'],
                    'timezone' => 'UTC',
                    'is_placeholder' => true,
                ], $member['user_id']);
                $this->memberImportHelper->getKey([
                    'user_id' => $userId,
                    'organization_id' => $this->organization->getKey(),
                ], [
                    'role' => Role::Placeholder->value,
                    'billable_rate' => $member['billable_rate'] === '' ? null : (int) $member['billable_rate'],
                ], $member['id']);
            }

            foreach ($projectsReader as $project) {
                $clientId = null;
                if ($project['client_id'] !== '') {
                    $clientId = $this->clientImportHelper->getKeyByExternalIdentifier($project['client_id']);
                    if ($clientId === null) {
                        throw new Exception('Client does not exist');
                    }
                }

                if (! $this->colorService->isValid($project['color'])) {
                    throw new ImportException('Invalid color');
                }

                $this->projectImportHelper->getKey([
                    'name' => $project['name'],
                    'organization_id' => $this->organization->getKey(),
                ], [
                    'color' => $project['color'],
                    'billable_rate' => $project['billable_rate'] === '' ? null : (int) $project['billable_rate'],
                    'is_public' => $project['is_public'] === 'true',
                    'client_id' => $clientId,
                    'is_billable' => $project['is_billable'] === 'true',
                    'archived_at' => $project['archived_at'] !== '' ? Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $project['archived_at'], 'UTC') : null,
                ], $project['id']);
            }

            foreach ($projectMembersReader as $projectMember) {
                $userId = $this->userImportHelper->getKeyByExternalIdentifier($projectMember['user_id']);
                $memberId = $this->memberImportHelper->getKeyByExternalIdentifier($projectMember['member_id']);
                $projectId = $this->projectImportHelper->getKeyByExternalIdentifier($projectMember['project_id']);
                $this->projectMemberImportHelper->getKey([
                    'project_id' => $projectId,
                    'member_id' => $memberId,
                ], [
                    'user_id' => $userId,
                    'billable_rate' => $projectMember['billable_rate'] === '' ? null : (int) $projectMember['billable_rate'],
                ], $projectMember['id']);
            }

            foreach ($tasksReader as $task) {
                $projectId = $this->projectImportHelper->getKeyByExternalIdentifier($task['project_id']);
                if ($projectId === null) {
                    throw new Exception('Project does not exist');
                }
                $this->taskImportHelper->getKey([
                    'name' => $task['name'],
                    'project_id' => $projectId,
                    'organization_id' => $this->organization->getKey(),
                ], [
                    'done_at' => $task['done_at'] !== '' ? Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $task['done_at'], 'UTC') : null,
                ], (string) $task['id']);
            }

            // Time entries
            foreach ($timeEntriesReader as $timeEntryRow) {
                $userId = $this->userImportHelper->getKeyByExternalIdentifier($timeEntryRow['user_id']);
                $memberId = $this->memberImportHelper->getKeyByExternalIdentifier($timeEntryRow['member_id']);
                $member = $this->memberImportHelper->getModelById($memberId);
                $clientId = null;
                if ($timeEntryRow['client_id'] !== '') {
                    $clientId = $this->clientImportHelper->getKeyByExternalIdentifier($timeEntryRow['client_id']);
                }
                $project = null;
                $projectId = null;
                $projectMember = null;
                if ($timeEntryRow['project_id'] !== '') {
                    $projectId = $this->projectImportHelper->getKeyByExternalIdentifier($timeEntryRow['project_id']);
                    $project = $this->projectImportHelper->getModelById($projectId);
                    $projectMember = $this->projectMemberImportHelper->getModel([
                        'project_id' => $projectId,
                        'member_id' => $memberId,
                    ]);
                }
                $taskId = null;
                if ($timeEntryRow['task_id'] !== '') {
                    $taskId = $this->taskImportHelper->getKeyByExternalIdentifier($timeEntryRow['task_id']);
                    $this->taskImportHelper->getModelById($taskId);
                }
                $timeEntry = new TimeEntry;
                $timeEntry->disableAuditing();
                $timeEntry->user_id = $userId;
                $timeEntry->member_id = $memberId;
                $timeEntry->task_id = $taskId;
                $timeEntry->project_id = $projectId;
                $timeEntry->client_id = $clientId;
                $timeEntry->organization_id = $this->organization->id;
                if (strlen($timeEntryRow['description']) > 500) {
                    throw new ImportException('Time entry description is too long');
                }
                $timeEntry->description = $timeEntryRow['description'];
                if (! in_array($timeEntryRow['billable'], ['true', 'false'], true)) {
                    throw new ImportException('Invalid billable value');
                }
                $timeEntry->billable = $timeEntryRow['billable'] === 'true';
                $timeEntry->tags = $this->getTags($timeEntryRow['tags']);
                $timeEntry->is_imported = true;

                try {
                    $start = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $timeEntryRow['start'], 'UTC');
                } catch (InvalidFormatException) {
                    throw new ImportException('Start date ("'.$timeEntryRow['start'].'") is invalid');
                }
                if ($start === null) {
                    throw new ImportException('Start date ("'.$timeEntryRow['start'].'") is invalid');
                }
                $timeEntry->start = $start->utc();

                if ($timeEntryRow['end'] !== '') {
                    try {
                        $end = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $timeEntryRow['end'], 'UTC');
                    } catch (InvalidFormatException) {
                        throw new ImportException('End date ("'.$timeEntryRow['end'].'") is invalid');
                    }
                    if ($end === null) {
                        throw new ImportException('End date ("'.$timeEntryRow['end'].'") is invalid');
                    }
                    $timeEntry->end = $end->utc();
                } else {
                    $timeEntry->end = null;
                }

                if ($timeEntryRow['still_active_email_sent_at'] !== '') {
                    try {
                        $stillActiveEmailSentAt = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $timeEntryRow['still_active_email_sent_at'], 'UTC');
                    } catch (InvalidFormatException) {
                        throw new ImportException('Still active email timestamp ("'.$timeEntryRow['still_active_email_sent_at'].'") is invalid');
                    }
                    if ($stillActiveEmailSentAt === null) {
                        throw new ImportException('Still active email timestamp ("'.$timeEntryRow['still_active_email_sent_at'].'") is invalid');
                    }
                    $timeEntry->still_active_email_sent_at = $stillActiveEmailSentAt->utc();
                } else {
                    $timeEntry->still_active_email_sent_at = null;
                }

                $timeEntry->billable_rate = $this->billableRateService->getBillableRateForTimeEntryWithGivenRelations(
                    $timeEntry,
                    $projectMember,
                    $project,
                    $member,
                    $this->organization
                );
                $timeEntry->save();
                $this->timeEntriesCreated++;
            }
            foreach ($this->projectImportHelper->getCachedModels() as $usedProject) {
                RecalculateSpentTimeForProject::dispatch($usedProject);
            }
            foreach ($this->taskImportHelper->getCachedModels() as $usedTask) {
                RecalculateSpentTimeForTask::dispatch($usedTask);
            }
        } catch (ImportException $exception) {
            throw $exception;
        } catch (Exception $exception) {
            report($exception);
            throw new ImportException('Unknown error');
        } finally {
            $temporaryDirectory?->delete();
            $temporaryDirectoryZip?->delete();
        }
    }

    /**
     * @return array<string>
     */
    private function getTags(string $tags): array
    {
        if (trim($tags) === '') {
            return [];
        }
        $tagsParsed = json_decode($tags);
        if ($tagsParsed === false || ! is_array($tagsParsed)) {
            return [];
        }
        $tagIds = [];
        foreach ($tagsParsed as $tagParsed) {
            if (! is_string($tagParsed) || ! Str::isUuid($tagParsed)) {
                continue;
            }
            $tagId = $this->tagImportHelper->getKeyByExternalIdentifier($tagParsed);
            $tagIds[] = $tagId;
        }

        return $tagIds;
    }

    #[Override]
    public function getName(): string
    {
        return __('importer.solidtime_importer.name');
    }

    #[Override]
    public function getDescription(): string
    {
        return __('importer.solidtime_importer.description');
    }
}
