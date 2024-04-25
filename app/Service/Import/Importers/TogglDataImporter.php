<?php

declare(strict_types=1);

namespace App\Service\Import\Importers;

use Exception;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use ValueError;
use ZipArchive;

class TogglDataImporter extends DefaultImporter
{
    /**
     * @throws ImportException
     */
    #[\Override]
    public function importData(string $data): void
    {
        try {
            $zip = new ZipArchive();
            $temporaryDirectory = TemporaryDirectory::make();
            file_put_contents($temporaryDirectory->path('import.zip'), $data);
            $res = $zip->open($temporaryDirectory->path('import.zip'), ZipArchive::RDONLY);
            if ($res !== true) {
                throw new ImportException('Invalid ZIP, error code: '.$res);
            }
            $temporaryDirectory = TemporaryDirectory::make();
            $zip->extractTo($temporaryDirectory->path());
            $zip->close();
            if (! file_exists($temporaryDirectory->path('clients.json'))) {
                throw new ImportException('File "clients.json" missing in ZIP');
            }
            $clientsFileContent = file_get_contents($temporaryDirectory->path('clients.json'));
            if ($clientsFileContent === false) {
                throw new ImportException('File "clients.json" can not be opened');
            }
            $clients = json_decode($clientsFileContent);
            if (! file_exists($temporaryDirectory->path('projects.json'))) {
                throw new ImportException('File "projects.json" missing in ZIP');
            }
            $projectsFileContent = file_get_contents($temporaryDirectory->path('projects.json'));
            if ($projectsFileContent === false) {
                throw new ImportException('File "projects.json" can not be opened');
            }
            $projects = json_decode($projectsFileContent);
            if (! file_exists($temporaryDirectory->path('tags.json'))) {
                throw new ImportException('File "tags.json" missing in ZIP');
            }
            $tagsFileContent = file_get_contents($temporaryDirectory->path('tags.json'));
            if ($tagsFileContent === false) {
                throw new ImportException('File "tags.json" can not be opened');
            }
            $tags = json_decode($tagsFileContent);
            if (! file_exists($temporaryDirectory->path('workspace_users.json'))) {
                throw new ImportException('File "workspace_users.json" missing in ZIP');
            }
            $workspaceUsersFileContent = file_get_contents($temporaryDirectory->path('workspace_users.json'));
            if ($workspaceUsersFileContent === false) {
                throw new ImportException('File "workspace_users.json" can not be opened');
            }
            $workspaceUsers = json_decode($workspaceUsersFileContent);
            foreach ($clients as $client) {
                $this->clientImportHelper->getKey([
                    'name' => $client->name,
                    'organization_id' => $this->organization->id,
                ], [], (string) $client->id);
            }
            foreach ($tags as $tag) {
                $this->tagImportHelper->getKey([
                    'name' => $tag->name,
                    'organization_id' => $this->organization->id,
                ], [], (string) $tag->id);
            }

            foreach ($workspaceUsers as $workspaceUser) {
                $this->userImportHelper->getKey([
                    'email' => $workspaceUser->email,
                ], [
                    'name' => $workspaceUser->name,
                    'timezone' => $workspaceUser->timezone ?? 'UTC',
                    'is_placeholder' => true,
                ], (string) $workspaceUser->uid);
            }

            foreach ($projects as $project) {
                $clientId = null;
                if ($project->client_id !== null) {
                    $clientId = $this->clientImportHelper->getKeyByExternalIdentifier((string) $project->client_id);
                    if ($clientId === null) {
                        throw new Exception('Client does not exist');
                    }
                }

                if (! $this->colorService->isValid($project->color)) {
                    throw new ImportException('Invalid color');
                }

                $projectId = $this->projectImportHelper->getKey([
                    'name' => $project->name,
                    'organization_id' => $this->organization->getKey(),
                ], [
                    'client_id' => $clientId,
                    'color' => $project->color,
                    'billable_rate' => $project->rate !== null ? (int) ($project->rate * 100) : null,
                ], (string) $project->id);

                if (! file_exists($temporaryDirectory->path('projects_users/'.$project->id.'.json'))) {
                    throw new ImportException('File "projects_users/'.$project->id.'.json" missing in ZIP');
                }
                $projectMembersFileContent = file_get_contents($temporaryDirectory->path('projects_users/'.$project->id.'.json'));
                if ($projectMembersFileContent === false) {
                    throw new ImportException('File "projects_users/'.$project->id.'.json" can not be opened');
                }
                $projectMembers = json_decode($projectMembersFileContent);
                foreach ($projectMembers as $projectMember) {
                    $this->projectMemberImportHelper->getKey([
                        'project_id' => $projectId,
                        'user_id' => $this->userImportHelper->getKeyByExternalIdentifier((string) $projectMember->user_id),
                    ], [
                        'billable_rate' => $projectMember->rate !== null ? (int) ($projectMember->rate * 100) : null,
                    ]);
                }
            }
            $projectIds = $this->projectImportHelper->getExternalIds();
            foreach ($projectIds as $projectIdExternal) {
                if (! file_exists($temporaryDirectory->path('tasks/'.$projectIdExternal.'.json'))) {
                    continue;
                }
                $tasksFileContent = file_get_contents($temporaryDirectory->path('tasks/'.$projectIdExternal.'.json'));
                if ($tasksFileContent === false) {
                    throw new ImportException('File "tasks/'.$projectIdExternal.'.json" can not be opened');
                }
                $tasks = json_decode($tasksFileContent);
                foreach ($tasks as $task) {
                    $projectId = $this->projectImportHelper->getKeyByExternalIdentifier((string) $projectIdExternal);

                    if ($projectId === null) {
                        throw new Exception('Project does not exist');
                    }
                    $this->taskImportHelper->getKey([
                        'name' => $task->name,
                        'project_id' => $projectId,
                        'organization_id' => $this->organization->getKey(),
                    ], [], (string) $task->id);
                }
            }
        } catch (ValueError $exception) {

        } catch (ImportException $exception) {
            throw $exception;
        } catch (Exception $exception) {
            report($exception);
            throw new ImportException('Unknown error');
        }
    }

    #[\Override]
    public function getName(): string
    {
        return __('importer.toggl_data_importer.name');
    }

    #[\Override]
    public function getDescription(): string
    {
        return __('importer.toggl_data_importer.description');
    }
}
