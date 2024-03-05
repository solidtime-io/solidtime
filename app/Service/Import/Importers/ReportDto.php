<?php

declare(strict_types=1);

namespace App\Service\Import\Importers;

class ReportDto
{
    public int $clientsCreated;

    public int $projectsCreated;

    public int $tasksCreated;

    public int $timeEntriesCreated;

    public int $tagsCreated;

    public int $usersCreated;

    public function __construct(int $clientsCreated, int $projectsCreated, int $tasksCreated, int $timeEntriesCreated, int $tagsCreated, int $usersCreated)
    {
        $this->clientsCreated = $clientsCreated;
        $this->projectsCreated = $projectsCreated;
        $this->tasksCreated = $tasksCreated;
        $this->timeEntriesCreated = $timeEntriesCreated;
        $this->tagsCreated = $tagsCreated;
        $this->usersCreated = $usersCreated;
    }

    /**
     * @return array{
     *    clients: array{
     *       created: int,
     *    },
     *    projects: array{
     *       created: int,
     *    },
     *    tasks: array{
     *       created: int,
     *    },
     *    time-entries: array{
     *       created: int,
     *    },
     *    tags: array{
     *       created: int,
     *    },
     *    users: array{
     *       created: int,
     *    }
     * }
     */
    public function toArray(): array
    {
        return [
            'clients' => [
                'created' => $this->clientsCreated,
            ],
            'projects' => [
                'created' => $this->projectsCreated,
            ],
            'tasks' => [
                'created' => $this->tasksCreated,
            ],
            'time-entries' => [
                'created' => $this->timeEntriesCreated,
            ],
            'tags' => [
                'created' => $this->tagsCreated,
            ],
            'users' => [
                'created' => $this->usersCreated,
            ],
        ];
    }
}
