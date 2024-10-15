<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Client;

use App\Models\Client;
use App\Models\Organization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Korridor\LaravelModelValidationRules\Rules\UniqueEloquent;

/**
 * @property Organization $organization Organization from model binding
 * @property Client|null $client Client from model binding
 */
class ClientUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            // Name of the client
            'name' => [
                'required',
                'string',
                'min:1',
                'max:255',
                UniqueEloquent::make(Client::class, 'name', function (Builder $builder): Builder {
                    /** @var Builder<Client> $builder */
                    return $builder->whereBelongsTo($this->organization, 'organization');
                })->ignore($this->client?->getKey())->withCustomTranslation('validation.client_name_already_exists'),
            ],
            'is_archived' => [
                'boolean',
            ],
        ];
    }

    public function getIsArchived(): bool
    {
        assert($this->has('is_archived'));

        return (bool) $this->input('is_archived');
    }
}
