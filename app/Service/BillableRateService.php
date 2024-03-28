<?php

declare(strict_types=1);

namespace App\Service;

use App\Models\Membership;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\TimeEntry;

class BillableRateService
{
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
        /** @var Membership|null $membership */
        $membership = Membership::query()
            ->where('user_id', '=', $timeEntry->user_id)
            ->where('organization_id', '=', $timeEntry->organization_id)
            ->first();
        if ($membership !== null && $membership->billable_rate !== null) {
            return $membership->billable_rate;
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
