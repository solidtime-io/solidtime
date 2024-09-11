<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Project;

use App\Models\Client;
use App\Models\Organization;
use App\Models\Project;
use App\Rules\ColorRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Korridor\LaravelModelValidationRules\Rules\ExistsEloquent;
use Korridor\LaravelModelValidationRules\Rules\UniqueEloquent;

/**
 * @property Organization $organization Organization from model binding
 * @property Project|null $project Project from model binding
 */
class ProjectUpdateRequest extends FormRequest
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
                'required',
                'string',
                'max:255',
                (new UniqueEloquent(Project::class, 'name', function (Builder $builder): Builder {
                    /** @var Builder<Project> $builder */
                    return $builder->whereBelongsTo($this->organization, 'organization');
                }))->ignore($this->project?->getKey())->withCustomTranslation('validation.project_name_already_exists'),
            ],
            'color' => [
                'required',
                'string',
                'max:255',
                new ColorRule,
            ],
            'is_billable' => [
                'required',
                'boolean',
            ],
            'is_archived' => [
                'boolean',
            ],
            'client_id' => [
                'nullable',
                new ExistsEloquent(Client::class, null, function (Builder $builder): Builder {
                    /** @var Builder<Client> $builder */
                    return $builder->whereBelongsTo($this->organization, 'organization');
                }),
            ],
            'billable_rate' => [
                'nullable',
                'integer',
                'min:0',
            ],
        ];
    }

    public function getIsArchived(): bool
    {
        assert($this->has('is_archived'));

        return (bool) $this->input('is_archived');
    }

    public function getBillableRate(): ?int
    {
        $input = $this->input('billable_rate');

        return $input !== null && $input !== 0 ? (int) $this->input('billable_rate') : null;
    }
}
