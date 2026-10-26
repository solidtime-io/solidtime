<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Models\User;
use App\Service\IdentityConfirmationService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;

class UpdateUserPassword implements UpdatesUserPasswords
{
    use PasswordValidationRules;

    /**
     * Validate and update the user's password.
     *
     * @param  array<string, string>  $input
     */
    public function update(User $user, array $input): void
    {
        // Users who may not log in with their password (e.g. SSO) have no password to change
        if (! app(IdentityConfirmationService::class)->confirmsWithPassword($user)) {
            throw ValidationException::withMessages([
                'current_password' => [__('Your account has no password to confirm this with. Contact your administrator.')],
            ])->errorBag('updatePassword');
        }

        Validator::make($input, [
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => $this->passwordRules(),
        ], [
            'current_password.current_password' => __('The provided password does not match your current password.'),
        ])->validateWithBag('updatePassword');

        $user->forceFill([
            'password' => Hash::make($input['password']),
        ])->save();
    }
}
