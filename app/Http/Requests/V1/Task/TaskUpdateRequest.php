<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Task;

use App\Models\Organization;
use App\Models\Project;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Korridor\LaravelModelValidationRules\Rules\ExistsEloquent;

/**
 * @property Organization $organization Organization from model binding
 */
class TaskUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'name' => [
                // TODO: unique
                'required',
                'string',
                'min:1',
                'max:255',
            ],
            'project_id' => [
                new ExistsEloquent(Project::class, null, function (Builder $builder): Builder {
                    /** @var Builder<Project> $builder */
                    return $builder->whereBelongsTo($this->organization, 'organization');
                }),
            ],
        ];
    }
}
