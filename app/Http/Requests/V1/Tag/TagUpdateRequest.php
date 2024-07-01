<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Tag;

use App\Models\Organization;
use App\Models\Tag;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Korridor\LaravelModelValidationRules\Rules\UniqueEloquent;

/**
 * @property Organization $organization Organization from model binding
 * @property Tag|null $tag Tag from model binding
 */
class TagUpdateRequest extends FormRequest
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
                'min:1',
                'max:255',
                (new UniqueEloquent(Tag::class, 'name', function (Builder $builder): Builder {
                    /** @var Builder<Tag> $builder */
                    return $builder->whereBelongsTo($this->organization, 'organization');
                }))->ignore($this->tag?->getKey())->withCustomTranslation('validation.tag_name_already_exists'),
            ],
        ];
    }
}
