<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\TimeEntry;

use App\Enums\TimeEntryType;
use App\Http\Requests\V1\BaseFormRequest;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Service\PermissionStore;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ConditionalRules;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\ProhibitedIf;
use Korridor\LaravelModelValidationRules\Rules\ExistsEloquent;

/**
 * @property Organization $organization Organization from model binding
 */
class TimeEntryUpdateRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string|\Closure|ValidationRule|\Illuminate\Contracts\Validation\Rule|ProhibitedIf|ConditionalRules>>
     */
    public function rules(): array
    {
        // Break restrictions need to apply based on the type the entry will have after the
        // update, not only when the payload itself contains type=break.
        $timeEntry = $this->route('timeEntry');
        $timeEntry = $timeEntry instanceof TimeEntry ? $timeEntry : null;
        $resultingType = $this->has('type')
            ? TimeEntryType::tryFrom((string) $this->input('type'))
            : $timeEntry?->type;
        $isBreak = $resultingType === TimeEntryType::Break;

        return [
            // ID of the organization member that the time entry should belong to
            'member_id' => [
                'string',
                ExistsEloquent::make(Member::class, null, function (Builder $builder): Builder {
                    /** @var Builder<Member> $builder */
                    return $builder->whereBelongsTo($this->organization, 'organization');
                })->uuid(),
            ],
            // ID of the project that the time entry should belong to
            'project_id' => [
                'nullable',
                'string',
                'required_with:task_id',
                Rule::prohibitedIf($isBreak),
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
            'task_id' => [
                'nullable',
                'string',
                Rule::prohibitedIf($isBreak),
                ExistsEloquent::make(Task::class, null, function (Builder $builder): Builder {
                    /** @var Builder<Task> $builder */
                    return $builder->whereBelongsTo($this->organization, 'organization');
                })->uuid(),
                ExistsEloquent::make(Task::class, null, function (Builder $builder): Builder {
                    /** @var Builder<Task> $builder */
                    return $builder->whereBelongsTo($this->organization, 'organization')
                        ->where('project_id', $this->input('project_id'));
                })->uuid()->withMessage(__('validation.task_belongs_to_project')),
            ],
            // Start of time entry (Format: "Y-m-d\TH:i:s\Z", UTC timezone, Example: "2000-02-22T14:58:59Z")
            'start' => [
                'date_format:Y-m-d\TH:i:s\Z',
            ],
            // End of time entry (Format: "Y-m-d\TH:i:s\Z", UTC timezone, Example: "2000-02-22T14:58:59Z")
            'end' => [
                'nullable',
                'date_format:Y-m-d\TH:i:s\Z',
                'after_or_equal:start',
            ],
            // Whether time entry is billable
            'billable' => [
                'sometimes',
                'boolean',
                Rule::when($isBreak, ['declined']),
            ],
            // Type of the time entry (work time or a break)
            'type' => [
                Rule::enum(TimeEntryType::class),
                function (string $attribute, mixed $value, \Closure $fail) use ($timeEntry): void {
                    // While breaks are disabled, entries that already are breaks may stay
                    // breaks, but converting a work entry to a break is not allowed.
                    if ($value === TimeEntryType::Break->value
                        && ! $this->organization->breaks_enabled
                        && $timeEntry?->type !== TimeEntryType::Break) {
                        $fail('Breaks are disabled for this organization.');
                    }
                },
            ],
            // Description of time entry
            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],
            // List of tag IDs
            'tags' => [
                'nullable',
                'array',
                Rule::prohibitedIf($isBreak),
            ],
            'tags.*' => [
                'string',
                ExistsEloquent::make(Tag::class, null, function (Builder $builder): Builder {
                    /** @var Builder<Tag> $builder */
                    return $builder->whereBelongsTo($this->organization, 'organization');
                })->uuid(),
            ],
        ];
    }
}
