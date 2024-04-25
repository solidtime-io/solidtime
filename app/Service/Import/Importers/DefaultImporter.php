<?php

declare(strict_types=1);

namespace App\Service\Import\Importers;

use App\Enums\Role;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use App\Service\ColorService;
use App\Service\Import\ImportDatabaseHelper;
use App\Service\TimezoneService;
use Illuminate\Database\Eloquent\Builder;

abstract class DefaultImporter implements ImporterContract
{
    protected Organization $organization;

    /**
     * @var ImportDatabaseHelper<User>
     */
    protected ImportDatabaseHelper $userImportHelper;

    /**
     * @var ImportDatabaseHelper<Project>
     */
    protected ImportDatabaseHelper $projectImportHelper;

    /**
     * @var ImportDatabaseHelper<Tag>
     */
    protected ImportDatabaseHelper $tagImportHelper;

    /**
     * @var ImportDatabaseHelper<Client>
     */
    protected ImportDatabaseHelper $clientImportHelper;

    /**
     * @var ImportDatabaseHelper<Task>
     */
    protected ImportDatabaseHelper $taskImportHelper;

    protected int $timeEntriesCreated;

    protected ColorService $colorService;

    protected TimezoneService $timezoneService;

    /**
     * @var ImportDatabaseHelper<ProjectMember>
     */
    protected ImportDatabaseHelper $projectMemberImportHelper;

    public function init(Organization $organization): void
    {
        $this->organization = $organization;
        $this->userImportHelper = new ImportDatabaseHelper(User::class, ['email'], true, function (Builder $builder) {
            /** @var Builder<User> $builder */
            return $builder->belongsToOrganization($this->organization);
        }, function (User $user) {
            $user->organizations()->attach($this->organization, [
                'role' => Role::Placeholder->value,
            ]);
        }, validate: [
            'name' => [
                'required',
                'max:255',
            ],
            'timezone' => [
                'required',
                'timezone:all',
            ],
        ]);
        $this->projectImportHelper = new ImportDatabaseHelper(Project::class, ['name', 'organization_id'], true, function (Builder $builder) {
            /** @var Builder<Project> $builder */
            return $builder->where('organization_id', $this->organization->id);
        }, validate: [
            'name' => [
                'required',
                'max:255',
            ],
            'billable_rate' => [
                'nullable',
                'integer',
            ],
        ]);
        $this->projectMemberImportHelper = new ImportDatabaseHelper(ProjectMember::class, ['project_id', 'user_id'], true, function (Builder $builder) {
            /** @var Builder<ProjectMember> $builder */
            return $builder->whereBelongsToOrganization($this->organization);
        }, validate: [
            'billable_rate' => [
                'nullable',
                'integer',
            ],
        ]);
        $this->tagImportHelper = new ImportDatabaseHelper(Tag::class, ['name', 'organization_id'], true, function (Builder $builder) {
            return $builder->where('organization_id', $this->organization->id);
        }, validate: [
            'name' => [
                'required',
                'max:255',
            ],
        ]);
        $this->clientImportHelper = new ImportDatabaseHelper(Client::class, ['name', 'organization_id'], true, function (Builder $builder) {
            return $builder->where('organization_id', $this->organization->id);
        }, validate: [
            'name' => [
                'required',
                'max:255',
            ],
        ]);
        $this->taskImportHelper = new ImportDatabaseHelper(Task::class, ['name', 'project_id', 'organization_id'], true, function (Builder $builder) {
            return $builder->where('organization_id', $this->organization->id);
        }, validate: [
            'name' => [
                'required',
                'max:500',
            ],
        ]);
        $this->timeEntriesCreated = 0;
        $this->colorService = app(ColorService::class);
        $this->timezoneService = app(TimezoneService::class);
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
