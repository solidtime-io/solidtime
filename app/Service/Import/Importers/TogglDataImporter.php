<?php

declare(strict_types=1);

namespace App\Service\Import\Importers;

use App\Models\Client;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use App\Service\ColorService;
use App\Service\Import\ImportDatabaseHelper;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use ZipArchive;

class TogglDataImporter implements ImporterContract
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

    private ColorService $colorService;

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
        $this->colorService = app(ColorService::class);
    }

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
            $zip->open($temporaryDirectory->path('import.zip'), ZipArchive::RDONLY);
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
        } catch (ImportException $exception) {
            throw $exception;
        } catch (Exception $exception) {
            report($exception);
            throw new ImportException('Unknown error');
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
            tagsCreated: $this->tagImportHelper->getCreatedCount(),
            usersCreated: $this->userImportHelper->getCreatedCount(),
        );
    }
}
