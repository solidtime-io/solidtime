<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\User;

use App\Http\Requests\V1\BaseFormRequest;
use App\Http\Requests\V1\Concerns\ConfirmsIdentity;
use Illuminate\Validation\Validator;

class UserDestroyRequest extends BaseFormRequest
{
    use ConfirmsIdentity;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string>>
     */
    public function rules(): array
    {
        return $this->identityConfirmationRules();
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            fn (Validator $validator) => $this->validateIdentityConfirmation($validator),
        ];
    }
}
