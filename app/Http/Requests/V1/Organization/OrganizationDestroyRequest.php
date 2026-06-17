<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Organization;

use App\Http\Requests\V1\BaseFormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Validator;

class OrganizationDestroyRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string>>
     */
    public function rules(): array
    {
        return [
            'password' => [
                'required',
                'string',
            ],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('password')) {
                    return;
                }

                $user = $this->user();
                $password = $this->input('password');

                if (! is_string($password) || $user === null || ! Hash::check($password, (string) $user->password)) {
                    $validator->errors()->add('password', __('The password is incorrect.'));
                }
            },
        ];
    }
}
