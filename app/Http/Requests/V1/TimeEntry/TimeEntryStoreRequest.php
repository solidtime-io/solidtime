<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\TimeEntry;

use App\Models\Organization;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Korridor\LaravelModelValidationRules\Rules\ExistsEloquent;

/**
 * @property Organization $organization Organization from model binding
 */
class TimeEntryStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            // ID of the user that the time entry should belong to
            'user_id' => [
                'required',
                'string',
                'uuid',
                new ExistsEloquent(User::class, null, function (Builder $builder): Builder {
                    /** @var Builder<User> $builder */
                    return $builder->belongsToOrganization($this->organization);
                }),
            ],
            // ID of the task that the time entry should belong to
            'task_id' => [
                'nullable',
                'string',
                'uuid',
                new ExistsEloquent(Task::class, null, function (Builder $builder): Builder {
                    /** @var Builder<Task> $builder */
                    return $builder->whereBelongsTo($this->organization, 'organization');
                }),
            ],
            // Start of time entry (ISO 8601 format, UTC timezone)
            'start' => [
                'required',
                'date_format:Y-m-d\TH:i:s\Z',
            ],
            // End of time entry (ISO 8601 format, UTC timezone)
            'end' => [
                'nullable',
                'date_format:Y-m-d\TH:i:s\Z',
                'after:start',
            ],
            // Description of time entry
            'description' => [
                'nullable',
                'string',
                'max:255',
            ],
            // List of tag IDs
            'tags' => [
                'nullable',
                'array',
            ],
            'tags.*' => [
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
