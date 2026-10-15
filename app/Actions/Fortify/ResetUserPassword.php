<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Models\User;
use App\Providers\FortifyServiceProvider;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    /**
     * Validate and reset the user's forgotten password.
     *
     * @param  array<string, string>  $input
     */
    public function reset(User $user, array $input): void
    {
        if (! FortifyServiceProvider::canResetPassword($user, $input)) {
            /** @var PasswordBroker $broker */
            $broker = Password::broker(config('fortify.passwords'));
            $broker->deleteToken($user);

            throw ValidationException::withMessages([
                'email' => [__('This password reset link is invalid.')],
            ]);
        }

        Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        $user->forceFill([
            'password' => Hash::make($input['password']),
        ])->save();
    }
}
