<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\User;

use App\Enums\Weekday;
use App\Http\Requests\V1\BaseFormRequest;
use App\Models\User;
use App\Rules\Base64ImageRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Korridor\LaravelModelValidationRules\Rules\UniqueEloquent;

/**
 * @property User $user User from model binding
 */
class UserUpdateRequest extends BaseFormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('email') && is_string($this->input('email'))) {
            $this->merge([
                'email' => Str::lower((string) $this->input('email')),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string|\Illuminate\Contracts\Validation\Rule|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'string',
                'max:255',
            ],
            'email' => [
                'email',
                'max:255',
                UniqueEloquent::make(User::class, 'email')->ignore($this->user->id)->query(function (Builder $query) {
                    /** @var Builder<User> $query */
                    return $query->where('is_placeholder', '=', false);
                }),
            ],
            'photo' => [
                'nullable',
                new Base64ImageRule,
            ],
            'timezone' => [
                'timezone:all',
            ],
            'week_start' => [
                Rule::enum(Weekday::class),
            ],
        ];
    }

    public function getName(): ?string
    {
        return $this->has('name') ? (string) $this->input('name') : null;
    }

    public function getEmail(): ?string
    {
        return $this->has('email') ? Str::lower((string) $this->input('email')) : null;
    }

    public function getTimezone(): ?string
    {
        return $this->has('timezone') ? (string) $this->input('timezone') : null;
    }

    public function getWeekStart(): ?Weekday
    {
        return $this->has('week_start') ? Weekday::from($this->input('week_start')) : null;
    }

    public function hasPhotoKey(): bool
    {
        return $this->has('photo');
    }

    public function getPhoto(): ?string
    {
        $value = $this->input('photo');

        return is_string($value) ? $value : null;
    }
}
