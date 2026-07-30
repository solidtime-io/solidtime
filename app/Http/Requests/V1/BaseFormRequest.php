<?php

declare(strict_types=1);

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class BaseFormRequest extends FormRequest
{
    /**
     * @return list<string>
     */
    protected function moneyRules(bool $bigInt = false): array
    {
        $rules = [
            'integer',
            'min:0',
        ];
        if ($bigInt) {
            $rules[] = 'max:9223372036854775807';
        } else {
            $rules[] = 'max:2147483647';
        }

        return $rules;
    }

    /**
     * Validation rules for a metadata object (string keys, string values, f.e. for external references like Stripe IDs).
     *
     * @return array<string, list<string>>
     */
    protected function metadataRules(): array
    {
        return [
            'metadata' => [
                'nullable',
                'array',
                'max:50',
            ],
            'metadata.*' => [
                'string',
                'max:500',
            ],
        ];
    }

    /**
     * @return array<string, string>|null
     */
    public function getMetadata(): ?array
    {
        assert($this->has('metadata'));
        $metadata = $this->input('metadata');

        return $metadata === null ? null : (array) $metadata;
    }
}
