<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Goal;

use App\Enums\Role;
use App\Enums\TagMatchType;
use App\Enums\TimeEntryType;
use App\Models\Client;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Service\Dto\GoalFiltersDto;
use App\Service\TimeEntryFilter;
use Illuminate\Contracts\Validation\Rule as LegacyValidationRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Korridor\LaravelModelValidationRules\Rules\ExistsEloquent;

/**
 * Shared validation rules for the filters of a goal.
 *
 * @property Organization $organization Organization from model binding
 */
trait GoalFilterRules
{
    /**
     * @param  bool  $forEveryMember  Whether the goal counts every member instead of one member
     * @return array<string, array<string|ValidationRule|LegacyValidationRule|\Closure>>
     */
    protected function filterRules(bool $forEveryMember): array
    {
        return [
            'filters' => [
                'sometimes',
                'array',
            ],
            // Filter by member IDs, member IDs are OR combined. Only for goals that count every member, null means every member.
            'filters.member_ids' => [
                'nullable',
                'array',
                // "prohibited" still lets null and an empty array through, both mean "no restriction"
                ...($forEveryMember ? [] : ['prohibited']),
            ],
            'filters.member_ids.*' => [
                'string',
                'distinct',
                ExistsEloquent::make(Member::class, null, function (Builder $builder): Builder {
                    /** @var Builder<Member> $builder */
                    return $builder->whereBelongsTo($this->organization, 'organization')
                        ->where('role', '!=', Role::Placeholder->value);
                })->uuid(),
            ],
            // Filter by project IDs, project IDs are OR combined, "none" matches entries without a project
            'filters.project_ids' => [
                'nullable',
                'array',
            ],
            'filters.project_ids.*' => [
                'string',
                $this->idOrNoneExistsInOrganization(Project::class),
            ],
            // Filter by task IDs, task IDs are OR combined, "none" matches entries without a task
            'filters.task_ids' => [
                'nullable',
                'array',
            ],
            'filters.task_ids.*' => [
                'string',
                $this->idOrNoneExistsInOrganization(Task::class),
            ],
            // Filter by tag IDs, tag IDs are OR combined, "none" matches entries without tags
            'filters.tag_ids' => [
                'nullable',
                'array',
            ],
            'filters.tag_ids.*' => [
                'string',
                $this->idOrNoneExistsInOrganization(Tag::class),
            ],
            'filters.tag_match_type' => [
                'nullable',
                'string',
                Rule::enum(TagMatchType::class),
            ],
            // Filter by client IDs, client IDs are OR combined, "none" matches entries without a client
            'filters.client_ids' => [
                'nullable',
                'array',
            ],
            'filters.client_ids.*' => [
                'string',
                $this->idOrNoneExistsInOrganization(Client::class),
            ],
            // Filter by billable status, null means both
            'filters.billable' => [
                'nullable',
                'boolean',
            ],
            // Filter by time entry type, null means both
            'filters.time_entry_type' => [
                'nullable',
                'string',
                Rule::enum(TimeEntryType::class),
            ],
        ];
    }

    /**
     * @param  class-string<Project|Task|Tag|Client>  $modelClass
     */
    private function idOrNoneExistsInOrganization(string $modelClass): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($modelClass): void {
            if ($value === TimeEntryFilter::NONE_VALUE) {
                return;
            }
            ExistsEloquent::make($modelClass, null, function (Builder $builder): Builder {
                /** @var Builder<Project|Task|Tag|Client> $builder */
                return $builder->whereBelongsTo($this->organization, 'organization');
            })->uuid()->validate($attribute, $value, $fail);
        };
    }

    public function getFilters(): GoalFiltersDto
    {
        $filters = new GoalFiltersDto;
        $filters->setMemberIds($this->input('filters.member_ids'));
        $filters->setProjectIds($this->input('filters.project_ids'));
        $filters->setTaskIds($this->input('filters.task_ids'));
        $filters->setTagIds($this->input('filters.tag_ids'));
        $filters->tagMatchType = $this->input('filters.tag_match_type') !== null ? TagMatchType::from($this->input('filters.tag_match_type')) : null;
        $filters->setClientIds($this->input('filters.client_ids'));
        $filters->billable = $this->input('filters.billable') !== null ? (bool) $this->input('filters.billable') : null;
        $filters->timeEntryType = $this->input('filters.time_entry_type') !== null ? TimeEntryType::from($this->input('filters.time_entry_type')) : null;

        return $filters;
    }
}
