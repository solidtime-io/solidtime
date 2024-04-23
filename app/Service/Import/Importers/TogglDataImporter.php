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
            $clientsFileContent = file_get_contents($temporaryDirectory->path('clients.json'));
            if ($clientsFileContent === false) {
                throw new ImportException('File clients.json missing in ZIP');
            }
            $clients = json_decode($clientsFileContent);
            $projectsFileContent = file_get_contents($temporaryDirectory->path('projects.json'));
            if ($projectsFileContent === false) {
                throw new ImportException('File projects.json missing in ZIP');
            }
            $projects = json_decode($projectsFileContent);
            $tagsFileContent = file_get_contents($temporaryDirectory->path('tags.json'));
            if ($tagsFileContent === false) {
                throw new ImportException('File tags.json missing in ZIP');
            }
            $tags = json_decode($tagsFileContent);
            $workspaceUsersFileContent = file_get_contents($temporaryDirectory->path('workspace_users.json'));
            if ($workspaceUsersFileContent === false) {
                throw new ImportException('File workspace_users.json missing in ZIP');
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

                $this->projectImportHelper->getKey([
                    'name' => $project->name,
                    'organization_id' => $this->organization->getKey(),
                ], [
                    'client_id' => $clientId,
                    'color' => $project->color,
                ], (string) $project->id);
            }
            foreach ($workspaceUsers as $workspaceUser) {
                $this->userImportHelper->getKey([
                    'email' => $workspaceUser->email,
                ], [
                    'name' => $workspaceUser->name,
                    'timezone' => $workspaceUser->timezone ?? 'UTC',
                    'is_placeholder' => true,
                ], (string) $workspaceUser->id);
            }
            $projectIds = $this->projectImportHelper->getExternalIds();
            foreach ($projectIds as $projectIdExternal) {
                $tasksFileContent = file_get_contents($temporaryDirectory->path('tasks/'.$projectIdExternal.'.json'));
                if ($tasksFileContent === false) {
                    throw new ImportException('File tasks/'.$projectIdExternal.'.json missing in ZIP');
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
