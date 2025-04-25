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
use Illuminate\Support\Str;
use Korridor\LaravelModelValidationRules\Rules\ExistsEloquent;
use Korridor\LaravelModelValidationRules\Rules\UniqueEloquent;

/**
 * @property Organization $organization Organization from model binding
 */
class ProjectStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            // Name of the project, the name needs to be unique per client and organization
            'name' => [
                'required',
                'string',
                'min:1',
                'max:255',
                UniqueEloquent::make(Project::class, 'name', function (Builder $builder): Builder {
                    /** @var Builder<Project> $builder */
                    $clientId = $this->input('client_id');
                    if (! is_string($clientId) || ! Str::isUuid($clientId)) {
                        $clientId = null;
                    }

                    return $builder->whereBelongsTo($this->organization, 'organization')
                        ->where('client_id', $clientId);
                })->withCustomTranslation('validation.project_name_already_exists'),
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
            'billable_rate' => [
                'nullable',
                'integer',
                'min:0',
                'max:2147483647',
            ],
            // ID of the client
            'client_id' => [
                'present',
                'nullable',
                ExistsEloquent::make(Client::class, null, function (Builder $builder): Builder {
                    /** @var Builder<Client> $builder */
                    return $builder->whereBelongsTo($this->organization, 'organization');
                })->uuid(),
            ],
            // Estimated time in seconds
            'estimated_time' => [
                'nullable',
                'integer',
                'min:0',
                'max:2147483647',
            ],
            // Whether the project is public
            'is_public' => [
                'boolean',
            ],
        ];
    }

    public function getIsPublic(): bool
    {
        return $this->has('is_public') && $this->boolean('is_public');
    }

    public function getBillableRate(): ?int
    {
        $input = $this->input('billable_rate');

        return $input !== null && $input !== 0 ? (int) $this->input('billable_rate') : null;
    }

    public function getEstimatedTime(): ?int
    {
        $input = $this->input('estimated_time');

        return $input !== null && $input !== 0 ? (int) $this->input('estimated_time') : null;
    }
}
