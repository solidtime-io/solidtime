<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Concerns;

use App\Models\User;
use App\Service\IdentityConfirmationService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Validator;

/**
 * Requires the user's "password". Users who can't confirm with a password can't do the action themselves.
 */
trait ConfirmsIdentity
{
    /**
     * @return array<string, array<string>>
     */
    protected function identityConfirmationRules(): array
    {
        $user = $this->user();
        $requiresPassword = ! $user instanceof User
            || app(IdentityConfirmationService::class)->confirmsWithPassword($user);

        return [
            'password' => [
                $requiresPassword ? 'required' : 'nullable',
                'string',
            ],
        ];
    }

    protected function validateIdentityConfirmation(Validator $validator): void
    {
        if ($validator->errors()->has('password')) {
            return;
        }

        $user = $this->user();
        if (! $user instanceof User) {
            $validator->errors()->add('password', __('The password is incorrect.'));

            return;
        }

        if (! app(IdentityConfirmationService::class)->confirmsWithPassword($user)) {
            $validator->errors()->add('password', __('Your account has no password to confirm this with. Contact your administrator.'));

            return;
        }

        $password = $this->input('password');

        if (! is_string($password) || ! Hash::check($password, (string) $user->password)) {
            $validator->errors()->add('password', __('The password is incorrect.'));
        }
    }
}
