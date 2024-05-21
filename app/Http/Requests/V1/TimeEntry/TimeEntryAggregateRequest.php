<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\TimeEntry;

use App\Enums\TimeEntryAggregationType;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Korridor\LaravelModelValidationRules\Rules\ExistsEloquent;

/**
 * @property Organization $organization
 */
class TimeEntryAggregateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string|ValidationRule|\Illuminate\Contracts\Validation\Rule>>
     */
    public function rules(): array
    {
        return [
            'group' => [
                'nullable',
                'required_with:group_2',
                Rule::enum(TimeEntryAggregationType::class),
            ],

            'sub_group' => [
                'nullable',
                Rule::enum(TimeEntryAggregationType::class),
            ],
            // Filter by member ID
            'member_id' => [
                'string',
                'uuid',
                new ExistsEloquent(Member::class, null, function (Builder $builder): Builder {
                    /** @var Builder<Member> $builder */
                    return $builder->whereBelongsTo($this->organization, 'organization');
                }),
            ],
            // Filter by multiple member IDs, member IDs are OR combined, but AND combined with the member_id parameter
            'member_ids' => [
                'array',
                'min:1',
            ],
            'member_ids.*' => [
                'string',
                'uuid',
                new ExistsEloquent(Member::class, null, function (Builder $builder): Builder {
                    /** @var Builder<Member> $builder */
                    return $builder->whereBelongsTo($this->organization, 'organization');
                }),
            ],

            // Filter by user ID
            'user_id' => [
                'string',
                'uuid',
                new ExistsEloquent(User::class, null, function (Builder $builder): Builder {
                    /** @var Builder<User> $builder */
                    return $builder->belongsToOrganization($this->organization);
                }),
            ],
            // Filter by project IDs, project IDs are OR combined
            'project_ids' => [
                'array',
                'min:1',
            ],
            'project_ids.*' => [
                'string',
                'uuid',
                new ExistsEloquent(Project::class, null, function (Builder $builder): Builder {
                    /** @var Builder<Project> $builder */
                    return $builder->whereBelongsTo($this->organization, 'organization');
                }),
            ],
            // Filter by tag IDs, tag IDs are AND combined
            'tag_ids' => [
                'array',
                'min:1',
            ],
            'tag_ids.*' => [
                'string',
                'uuid',
                new ExistsEloquent(Tag::class, null, function (Builder $builder): Builder {
                    /** @var Builder<Tag> $builder */
                    return $builder->whereBelongsTo($this->organization, 'organization');
                }),
            ],
            // Filter by task IDs, task IDs are OR combined
            'task_ids' => [
                'array',
                'min:1',
            ],
            'task_ids.*' => [
                'string',
                'uuid',
                new ExistsEloquent(Task::class, null, function (Builder $builder): Builder {
                    return $builder->whereBelongsTo($this->organization, 'organization');
                }),
            ],
            // Filter only time entries that have a start date after the given timestamp in UTC (example: 2021-01-01T00:00:00Z)
            'start' => [
                'nullable',
                'string',
                'date_format:Y-m-d\TH:i:s\Z',
                'before:end',
            ],
            // Filter only time entries that have a start date before the given timestamp in UTC (example: 2021-01-01T00:00:00Z)
            'end' => [
                'nullable',
                'string',
                'date_format:Y-m-d\TH:i:s\Z',
            ],
            // Filter by active status (active means has no end date, is still running)
            'active' => [
                'string',
                'in:true,false',
            ],
            // Filter by billable status
            'billable' => [
                'string',
                'in:true,false',
            ],
            'fill_gaps_in_time_groups' => [
                'string',
                'in:true,false',
            ],
        ];
    }

    public function getGroup(): ?TimeEntryAggregationType
    {
        return $this->get('group') !== null ? TimeEntryAggregationType::from($this->get('group')) : null;
    }

    public function getSubGroup(): ?TimeEntryAggregationType
    {
        return $this->get('sub_group') !== null ? TimeEntryAggregationType::from($this->get('sub_group')) : null;
    }

    public function getFillGapsInTimeGroups(): bool
    {
        return $this->has('fill_gaps_in_time_groups') && $this->get('fill_gaps_in_time_groups') === 'true';
    }

    public function getStart(): ?Carbon
    {
        return $this->get('start') !== null ? Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $this->get('start'), 'UTC') : null;
    }

    public function getEnd(): ?Carbon
    {
        return $this->get('end') !== null ? Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $this->get('end'), 'UTC') : null;
    }
}
