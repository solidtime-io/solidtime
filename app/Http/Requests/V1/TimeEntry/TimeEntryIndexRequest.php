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
            'before' => [
                'nullable',
                'string',
                'date_format:Y-m-d',
                'before:after',
            ],
            'after' => [
                'nullable',
                'string',
                'date_format:Y-m-d',
            ],
        ];
    }
}
