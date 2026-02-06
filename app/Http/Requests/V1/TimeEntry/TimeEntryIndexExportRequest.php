<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\TimeEntry;

use App\Enums\ExportFormat;
use App\Enums\TimeEntryRoundingType;
use App\Models\Client;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Service\TimeEntryFilter;
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
     * @return array<string, array<string|ValidationRule|\Illuminate\Contracts\Validation\Rule|\Closure>>
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
            // Filter by client IDs, client IDs are OR combined
            'client_ids' => [
                'array',
                'min:1',
            ],
            'client_ids.*' => [
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === TimeEntryFilter::NONE_VALUE) {
                        return;
                    }
                    ExistsEloquent::make(Client::class, null, function (Builder $builder): Builder {
                        /** @var Builder<Client> $builder */
                        return $builder->whereBelongsTo($this->organization, 'organization');
                    })->uuid()->validate($attribute, $value, $fail);
                },
            ],
            // Filter by project IDs, project IDs are OR combined
            'project_ids' => [
                'array',
                'min:1',
            ],
            'project_ids.*' => [
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === TimeEntryFilter::NONE_VALUE) {
                        return;
                    }
                    ExistsEloquent::make(Project::class, null, function (Builder $builder): Builder {
                        /** @var Builder<Project> $builder */
                        return $builder->whereBelongsTo($this->organization, 'organization');
                    })->uuid()->validate($attribute, $value, $fail);
                },
            ],
            // Filter by tag IDs, tag IDs are OR combined
            'tag_ids' => [
                'array',
                'min:1',
            ],
            'tag_ids.*' => [
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === TimeEntryFilter::NONE_VALUE) {
                        return;
                    }
                    ExistsEloquent::make(Tag::class, null, function (Builder $builder): Builder {
                        /** @var Builder<Tag> $builder */
                        return $builder->whereBelongsTo($this->organization, 'organization');
                    })->uuid()->validate($attribute, $value, $fail);
                },
            ],
            // Filter by task IDs, task IDs are OR combined
            'task_ids' => [
                'array',
                'min:1',
            ],
            'task_ids.*' => [
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === TimeEntryFilter::NONE_VALUE) {
                        return;
                    }
                    ExistsEloquent::make(Task::class, null, function (Builder $builder): Builder {
                        /** @var Builder<Task> $builder */
                        return $builder->whereBelongsTo($this->organization, 'organization');
                    })->uuid()->validate($attribute, $value, $fail);
                },
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
            // Rounding type defined where the end of each time entry should be rounded to. For example: nearest rounds the end to the nearest x minutes group. Rounding per time entry is activated if `rounding_type` and `rounding_minutes` is not null.
            'rounding_type' => [
                'nullable',
                'string',
                Rule::enum(TimeEntryRoundingType::class),
            ],
            // Defines the length of the interval that the time entry rounding rounds to.
            'rounding_minutes' => [
                'nullable',
                'numeric',
                'integer',
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

    public function getRoundingType(): ?TimeEntryRoundingType
    {
        if (! $this->has('rounding_type') || $this->validated('rounding_type') === null) {
            return null;
        }

        return TimeEntryRoundingType::from($this->validated('rounding_type'));
    }

    public function getRoundingMinutes(): ?int
    {
        if (! $this->has('rounding_minutes') || $this->validated('rounding_minutes') === null) {
            return null;
        }

        return (int) $this->validated('rounding_minutes');
    }
}
