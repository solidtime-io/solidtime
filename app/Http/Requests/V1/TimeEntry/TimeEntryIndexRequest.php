<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\TimeEntry;

use App\Models\Organization;
use App\Models\User;
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
            // Filter by user ID
            'user_id' => [
                'string',
                'uuid',
                new ExistsEloquent(User::class, null, function (Builder $builder): Builder {
                    /** @var Builder<User> $builder */
                    return $builder->whereHas('organizations', function (Builder $builder) {
                        /** @var Builder<Organization> $builder */
                        return $builder->whereKey($this->organization->getKey());
                    });
                }),
            ],
            // Filter only time entries that have a start date before (not including) the given date (example: 2021-12-31)
            'before' => [
                'nullable',
                'string',
                'date_format:Y-m-d\TH:i:s\Z',
                'before:after',
            ],
            // Filter only time entries that have a start date after (not including) the given date (example: 2021-12-31)
            'after' => [
                'nullable',
                'string',
                'date_format:Y-m-d\TH:i:s\Z',
            ],
            // Filter only time entries that are active (have no end date, are still running)
            'active' => [
                'boolean',
            ],
            // Limit the number of returned time entries
            'limit' => [
                'integer',
                'min:1',
                'max:500',
            ],
            // Filter makes sure that only time entries of a whole date are returned
            'only_full_dates' => [
                'boolean',
            ],
        ];
    }
}
