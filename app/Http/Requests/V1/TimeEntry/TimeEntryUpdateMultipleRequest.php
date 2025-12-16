<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\TimeEntry;

use App\Http\Requests\V1\BaseFormRequest;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Service\PermissionStore;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Korridor\LaravelModelValidationRules\Rules\ExistsEloquent;

/**
 * @property Organization $organization Organization from model binding
 */
class TimeEntryUpdateMultipleRequest extends BaseFormRequest
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
                ExistsEloquent::make(Member::class, null, function (Builder $builder): Builder {
                    /** @var Builder<Member> $builder */
                    return $builder->whereBelongsTo($this->organization, 'organization');
                })->uuid(),
            ],
            // ID of the project that the time entry should belong to
            'changes.project_id' => [
                'nullable',
                'string',
                'required_with:task_id',
                ExistsEloquent::make(Project::class, null, function (Builder $builder): Builder {
                    /** @var Builder<Project> $builder */
                    $builder = $builder->whereBelongsTo($this->organization, 'organization');

                    // If user doesn't have 'all' permission for time entries or projects, only allow access to public projects or projects they're a member of
                    $permissionStore = app(PermissionStore::class);
                    if (! $permissionStore->has($this->organization, 'time-entries:update:all')
                        && ! $permissionStore->has($this->organization, 'projects:view:all')) {
                        $builder = $builder->visibleByEmployee(Auth::user());
                    }

                    return $builder;
                })->uuid(),
            ],
            // ID of the task that the time entry should belong to
            'changes.task_id' => [
                'nullable',
                'string',
                ExistsEloquent::make(Task::class, null, function (Builder $builder): Builder {
                    /** @var Builder<Task> $builder */
                    return $builder->whereBelongsTo($this->organization, 'organization');
                })->uuid(),
                ExistsEloquent::make(Task::class, null, function (Builder $builder): Builder {
                    /** @var Builder<Task> $builder */
                    return $builder->whereBelongsTo($this->organization, 'organization')
                        ->where('project_id', $this->input('changes.project_id'));
                })->uuid()->withMessage(__('validation.task_belongs_to_project')),
            ],
            // Whether time entry is billable
            'changes.billable' => [
                'boolean',
            ],
            // Description of time entry
            'changes.description' => [
                'nullable',
                'string',
                'max:5000',
            ],
            // List of tag IDs
            'changes.tags' => [
                'nullable',
                'array',
            ],
            'changes.tags.*' => [
                'string',
                ExistsEloquent::make(Tag::class, null, function (Builder $builder): Builder {
                    /** @var Builder<Tag> $builder */
                    return $builder->whereBelongsTo($this->organization, 'organization');
                })->uuid(),
            ],
        ];
    }
}
