<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Project;

use App\Models\Client;
use App\Models\Organization;
use App\Rules\ColorRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Korridor\LaravelModelValidationRules\Rules\ExistsEloquent;

/**
 * @property Organization $organization Organization from model binding
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
                // TODO: unique
                'required',
                'string',
                'max:255',
            ],
            'color' => [
                'required',
                'string',
                'max:255',
                new ColorRule(),
            ],
            'is_billable' => [
                'required',
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
            'billable_rate_update_time_entries' => [
                'string',
                'in:true,false',
            ],
        ];
    }

    public function getBillableRate(): ?int
    {
        $input = $this->input('billable_rate');

        return $input !== null && $input !== 0 ? (int) $this->input('billable_rate') : null;
    }

    public function getBillableRateUpdateTimeEntries(): bool
    {
        return $this->has('billable_rate_update_time_entries') &&
            $this->input('billable_rate_update_time_entries') === 'true';
    }
}
