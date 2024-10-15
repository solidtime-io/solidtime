<?php

declare(strict_types=1);

namespace App\Service\Import\Importers;

use App\Models\Client;
use App\Models\Member;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use App\Service\BillableRateService;
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
     * @var ImportDatabaseHelper<Member>
     */
    protected ImportDatabaseHelper $memberImportHelper;

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

    /**
     * @var ImportDatabaseHelper<OrganizationInvitation>
     */
    protected ImportDatabaseHelper $organizationInvitationsImportHelper;

    protected BillableRateService $billableRateService;

    public function init(Organization $organization): void
    {
        $this->organization = $organization;
        $this->userImportHelper = new ImportDatabaseHelper(User::class, ['email'], true, function (Builder $builder) {
            /** @var Builder<User> $builder */
            return $builder->belongsToOrganization($this->organization);
        }, null, validate: [
            'name' => [
                'required',
                'max:255',
            ],
            'timezone' => [
                'required',
                'timezone:all',
            ],
        ]);
        $this->memberImportHelper = new ImportDatabaseHelper(Member::class, ['user_id', 'organization_id'], true, function (Builder $builder) {
            /** @var Builder<Member> $builder */
            return $builder->whereBelongsTo($this->organization, 'organization');
        }, null, validate: [
            'role' => [
                'required',
                'string',
                'in:placeholder',
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
            'is_billable' => [
                'required',
                'boolean',
            ],
            'billable_rate' => [
                'nullable',
                'integer',
                'max:2147483647',
            ],
        ], beforeSave: function (Project $project): void {
            if ($project->billable_rate === 0) {
                $project->billable_rate = null;
            }
        });
        $this->projectMemberImportHelper = new ImportDatabaseHelper(ProjectMember::class, ['project_id', 'member_id'], true, function (Builder $builder): Builder {
            /** @var Builder<ProjectMember> $builder */
            return $builder->whereBelongsToOrganization($this->organization);
        }, validate: [
            'billable_rate' => [
                'nullable',
                'integer',
                'max:2147483647',
            ],
        ], beforeSave: function (ProjectMember $projectMember): void {
            if ($projectMember->billable_rate === 0) {
                $projectMember->billable_rate = null;
            }
        });
        $this->tagImportHelper = new ImportDatabaseHelper(Tag::class, ['name', 'organization_id'], true, function (Builder $builder): Builder {
            /** @var Builder<Tag> $builder */
            return $builder->where('organization_id', $this->organization->id);
        }, validate: [
            'name' => [
                'required',
                'max:255',
            ],
        ]);
        $this->clientImportHelper = new ImportDatabaseHelper(Client::class, ['name', 'organization_id'], true, function (Builder $builder): Builder {
            /** @var Builder<Client> $builder */
            return $builder->where('organization_id', $this->organization->id);
        }, validate: [
            'name' => [
                'required',
                'max:255',
            ],
        ]);
        $this->taskImportHelper = new ImportDatabaseHelper(Task::class, ['name', 'project_id', 'organization_id'], true, function (Builder $builder): Builder {
            /** @var Builder<Task> $builder */
            return $builder->where('organization_id', $this->organization->id);
        }, validate: [
            'name' => [
                'required',
                'max:500',
            ],
        ]);
        $this->organizationInvitationsImportHelper = new ImportDatabaseHelper(OrganizationInvitation::class, ['email', 'organization_id'], true, function (Builder $builder) {
            /** @var Builder<OrganizationInvitation> $builder */
            return $builder->where('organization_id', $this->organization->id);
        }, validate: [
            'email' => [
                'required',
                'email',
                'max:255',
            ],
        ]);
        $this->timeEntriesCreated = 0;
        $this->colorService = app(ColorService::class);
        $this->timezoneService = app(TimezoneService::class);
        $this->billableRateService = app(BillableRateService::class);
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
