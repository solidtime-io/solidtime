<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\TimeEntry;

use App\Models\Member;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Korridor\LaravelModelValidationRules\Rules\ExistsEloquent;

/**
 * @property Organization $organization Organization from model binding
 */
class TimeEntryUpdateMultipleRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'ids' => [
                'required',
                'array',
            ],
            'ids.*' => [
                'string',
                'uuid',
            ],
            'changes' => [
                'required',
                'array',
            ],
            // ID of the organization member that the time entry should belong to
            'changes.member_id' => [
                'string',
                'uuid',
                new ExistsEloquent(Member::class, null, function (Builder $builder): Builder {
                    /** @var Builder<Member> $builder */
                    return $builder->whereBelongsTo($this->organization, 'organization');
                }),
            ],
            // ID of the project that the time entry should belong to
            'changes.project_id' => [
                'nullable',
                'string',
                'uuid',
                'required_with:task_id',
                new ExistsEloquent(Project::class, null, function (Builder $builder): Builder {
                    /** @var Builder<Project> $builder */
                    return $builder->whereBelongsTo($this->organization, 'organization');
                }),
            ],
            // ID of the task that the time entry should belong to
            'changes.task_id' => [
                'nullable',
                'string',
                'uuid',
                new ExistsEloquent(Task::class, null, function (Builder $builder): Builder {
                    /** @var Builder<Task> $builder */
                    return $builder->whereBelongsTo($this->organization, 'organization');
                }),
                (new ExistsEloquent(Task::class, null, function (Builder $builder): Builder {
                    /** @var Builder<Task> $builder */
                    return $builder->whereBelongsTo($this->organization, 'organization')
                        ->where('project_id', $this->input('changes.project_id'));
                }))->withMessage(__('validation.task_belongs_to_project')),
            ],
            // Whether time entry is billable
            'changes.billable' => [
                'boolean',
            ],
            // Description of time entry
            'changes.description' => [
                'nullable',
                'string',
                'max:500',
            ],
            // List of tag IDs
            'changes.tags' => [
                'nullable',
                'array',
            ],
            'changes.tags.*' => [
                'string',
                'uuid',
                new ExistsEloquent(Tag::class, null, function (Builder $builder): Builder {
                    /** @var Builder<Tag> $builder */
                    return $builder->whereBelongsTo($this->organization, 'organization');
                }),
            ],
        ];
    }
}
