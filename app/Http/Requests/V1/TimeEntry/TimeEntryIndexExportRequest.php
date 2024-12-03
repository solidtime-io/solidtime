<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\TimeEntry;

use App\Enums\ExportFormat;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Korridor\LaravelModelValidationRules\Rules\ExistsEloquent;

/**
 * @property Organization $organization
 */
class TimeEntryIndexExportRequest extends TimeEntryIndexRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string|ValidationRule|\Illuminate\Contracts\Validation\Rule>>
     */
    public function rules(): array
    {
        return [
            'format' => [
                'required',
                'string',
                Rule::enum(ExportFormat::class),
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
            // Filter by tag IDs, tag IDs are OR combined
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
                    /** @var Builder<Task> $builder */
                    return $builder->whereBelongsTo($this->organization, 'organization');
                }),
            ],
            // Filter only time entries that have a start date after the given timestamp in UTC (example: 2021-01-01T00:00:00Z)
            'start' => [
                'required',
                'string',
                'date_format:Y-m-d\TH:i:s\Z',
                'before:end',
            ],
            // Filter only time entries that have a start date before the given timestamp in UTC (example: 2021-01-01T00:00:00Z)
            'end' => [
                'required',
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
            // Filter makes sure that only time entries of a whole date are returned
            'only_full_dates' => [
                'string',
                'in:true,false',
            ],
            'debug' => [
                'string',
                'in:true,false',
            ],
        ];
    }

    public function getDebug(): bool
    {
        return $this->input('debug', 'false') === 'true';
    }

    public function getStart(): Carbon
    {
        $start = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $this->input('start'), 'UTC');
        if ($start === null) {
            throw new \LogicException('Start date validation is not working');
        }

        return $start;
    }

    public function getEnd(): Carbon
    {
        $end = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $this->input('end'), 'UTC');
        if ($end === null) {
            throw new \LogicException('End date validation is not working');
        }

        return $end;
    }

    public function getOnlyFullDates(): bool
    {
        return $this->input('only_full_dates', 'false') === 'true';
    }

    public function getFormatValue(): ExportFormat
    {
        return ExportFormat::from($this->validated('format'));
    }
}
