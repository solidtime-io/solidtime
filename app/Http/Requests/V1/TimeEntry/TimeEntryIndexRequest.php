<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\TimeEntry;

use App\Models\Client;
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
 * @property Organization $organization
 */
class TimeEntryIndexRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            // Filter by member ID
            'member_id' => [
                'string',
                ExistsEloquent::make(Member::class, null, function (Builder $builder): Builder {
                    /** @var Builder<Member> $builder */
                    return $builder->whereBelongsTo($this->organization, 'organization');
                })->uuid(),
            ],
            // Filter by multiple member IDs, member IDs are OR combined, but AND combined with the member_id parameter
            'member_ids' => [
                'array',
                'min:1',
            ],
            'member_ids.*' => [
                'string',
                ExistsEloquent::make(Member::class, null, function (Builder $builder): Builder {
                    /** @var Builder<Member> $builder */
                    return $builder->whereBelongsTo($this->organization, 'organization');
                })->uuid(),
            ],
            // Filter by client IDs, client IDs are OR combined
            'client_ids' => [
                'array',
                'min:1',
            ],
            'client_ids.*' => [
                'string',
                ExistsEloquent::make(Client::class, null, function (Builder $builder): Builder {
                    /** @var Builder<Client> $builder */
                    return $builder->whereBelongsTo($this->organization, 'organization');
                })->uuid(),
            ],
            // Filter by project IDs, project IDs are OR combined
            'project_ids' => [
                'array',
                'min:1',
            ],
            'project_ids.*' => [
                'string',
                ExistsEloquent::make(Project::class, null, function (Builder $builder): Builder {
                    /** @var Builder<Project> $builder */
                    return $builder->whereBelongsTo($this->organization, 'organization');
                })->uuid(),
            ],
            // Filter by tag IDs, tag IDs are AND combined
            'tag_ids' => [
                'array',
                'min:1',
            ],
            'tag_ids.*' => [
                'string',
                ExistsEloquent::make(Tag::class, null, function (Builder $builder): Builder {
                    /** @var Builder<Tag> $builder */
                    return $builder->whereBelongsTo($this->organization, 'organization');
                })->uuid(),
            ],
            // Filter by task IDs, task IDs are OR combined
            'task_ids' => [
                'array',
                'min:1',
            ],
            'task_ids.*' => [
                'string',
                ExistsEloquent::make(Task::class, null, function (Builder $builder): Builder {
                    /** @var Builder<Task> $builder */
                    return $builder->whereBelongsTo($this->organization, 'organization');
                })->uuid(),
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
            // Limit the number of returned time entries (default: 150)
            'limit' => [
                'integer',
                'min:1',
                'max:500',
            ],
            // Skip the first n time entries (default: 0)
            'offset' => [
                'integer',
                'min:0',
                'max:2147483647',
            ],
            // Filter makes sure that only time entries of a whole date are returned
            'only_full_dates' => [
                'string',
                'in:true,false',
            ],
        ];
    }

    public function getOnlyFullDates(): bool
    {
        return $this->input('only_full_dates', 'false') === 'true';
    }

    public function getLimit(): int
    {
        return $this->has('limit') ? (int) $this->validated('limit', 100) : 100;
    }

    public function getOffset(): int
    {
        return $this->has('offset') ? (int) $this->validated('offset', 0) : 0;
    }
}
