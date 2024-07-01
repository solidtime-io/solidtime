<?php

declare(strict_types=1);

namespace App\Service;

use App\Models\Member;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\TimeEntry;
use Illuminate\Database\Eloquent\Builder;

class BillableRateService
{
    public function updateTimeEntriesBillableRateForProjectMember(ProjectMember $projectMember): void
    {
        TimeEntry::query()
            ->where('billable', '=', true)
            ->where('member_id', '=', $projectMember->member_id)
            ->where('project_id', '=', $projectMember->project_id)
            ->update(['billable_rate' => $projectMember->billable_rate]);
    }

    public function updateTimeEntriesBillableRateForProject(Project $project): void
    {
        TimeEntry::query()
            ->where('billable', '=', true)
            ->where('organization_id', '=', $project->organization_id)
            ->whereBelongsTo($project, 'project')
            ->whereDoesntHave('member', function (Builder $query) use ($project) {
                /** @var Builder<Member> $query */
                $query->whereHas('projectMembers', function (Builder $query) use ($project) {
                    /** @var Builder<ProjectMember> $query */
                    $query->whereBelongsTo($project, 'project')
                        ->whereNotNull('billable_rate');
                });
            })
            ->update(['billable_rate' => $project->billable_rate]);
    }

    public function updateTimeEntriesBillableRateForMember(Member $member): void
    {
        TimeEntry::query()
            ->where('billable', '=', true)
            ->where('organization_id', '=', $member->organization_id)
            ->where('member_id', '=', $member->getKey())
            ->whereDoesntHave('project', function (Builder $builder) use ($member): void {
                /** @var Builder<Project> $builder */
                $builder->whereNotNull('billable_rate')
                    ->orWhereHas('members', function (Builder $builder) use ($member): void {
                        /** @var Builder<ProjectMember> $builder */
                        $builder->whereNotNull('billable_rate')
                            ->where('member_id', '=', $member->getKey());
                    });
            })
            ->update(['billable_rate' => $member->billable_rate]);
    }

    public function updateTimeEntriesBillableRateForOrganization(Organization $organization): void
    {
        TimeEntry::query()
            ->where('billable', '=', true)
            ->where('organization_id', '=', $organization->getKey())
            ->whereDoesntHave('member', function (Builder $builder) {
                /** @var Builder<Member> $builder */
                $builder->whereNotNull('billable_rate');
            })
            ->whereDoesntHave('project', function (Builder $builder): void {
                /** @var Builder<Project> $builder */
                $builder->whereNotNull('billable_rate')
                    ->orWhereHas('members', function (Builder $builder): void {
                        /** @var Builder<ProjectMember> $builder */
                        $builder->whereNotNull('billable_rate')
                            ->whereRaw('member_id = time_entries.member_id');
                    });
            })
            ->update(['billable_rate' => $organization->billable_rate]);
    }

    public function getBillableRateForTimeEntryWithGivenRelations(TimeEntry $timeEntry, ?ProjectMember $projectMember, ?Project $project, ?Member $member, ?Organization $organization): ?int
    {
        if (! $timeEntry->billable) {
            return null;
        }
        if ($projectMember !== null && $projectMember->billable_rate !== null) {
            return $projectMember->billable_rate;
        }
        if ($project !== null && $project->billable_rate !== null) {
            return $project->billable_rate;
        }
        if ($member !== null && $member->billable_rate !== null) {
            return $member->billable_rate;
        }
        if ($organization !== null && $organization->billable_rate !== null) {
            return $organization->billable_rate;
        }

        return null;
    }

    public function getBillableRateForTimeEntry(TimeEntry $timeEntry): ?int
    {
        if (! $timeEntry->billable) {
            return null;
        }
        if ($timeEntry->project_id !== null) {
            // Project member rate
            /** @var ProjectMember|null $projectMember */
            $projectMember = ProjectMember::query()
                ->where('user_id', '=', $timeEntry->user_id)
                ->where('project_id', '=', $timeEntry->project_id)
                ->first();
            if ($projectMember !== null && $projectMember->billable_rate !== null) {
                return $projectMember->billable_rate;
            }

            // Project rate
            /** @var Project|null $project */
            $project = Project::find($timeEntry->project_id);
            if ($project !== null && $project->billable_rate !== null) {
                return $project->billable_rate;
            }
        }
        // Member rate
        /** @var Member|null $member */
        $member = Member::query()
            ->where('user_id', '=', $timeEntry->user_id)
            ->where('organization_id', '=', $timeEntry->organization_id)
            ->first();
        if ($member !== null && $member->billable_rate !== null) {
            return $member->billable_rate;
        }

        // Organization rate
        /** @var Organization|null $organization */
        $organization = Organization::query()
            ->where('id', '=', $timeEntry->organization_id)
            ->first();
        if ($organization !== null && $organization->billable_rate !== null) {
            return $organization->billable_rate;
        }

        return null;
    }
}
