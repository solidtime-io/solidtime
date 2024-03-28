<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\ProjectMember;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Korridor\LaravelModelValidationRules\Rules\ExistsEloquent;

/**
 * @property Organization $organization Organization from model binding
 */
class ProjectMemberStoreRequest extends FormRequest
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
                'required',
                'uuid',
                new ExistsEloquent(User::class, null, function (Builder $builder): Builder {
                    /** @var Builder<User> $builder */
                    return $builder->belongsToOrganization($this->organization);
                }),
            ],
            'billable_rate' => [
                'nullable',
                'integer',
                'min:0',
            ],
        ];
    }
}
