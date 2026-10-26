<?php

declare(strict_types=1);

namespace App\Service;

use App\Models\User;

/**
 * Decides how users authenticate. Core only has password login.
 * The SSO extension rebinds this to add single sign-on.
 */
class AuthenticationContract
{
    public const string EMAIL_CHANGE_LOCKED_MESSAGE = 'Your email address is managed by your organization and cannot be changed here. Ask your administrator to change it.';

    /**
     * Whether the user may use their local password: to log in, to reset it and to confirm their identity.
     * Returning false for a user without a password also keeps "forgot password" from creating their first one.
     */
    public function isPasswordLoginAllowed(User $user): bool
    {
        return true;
    }

    /**
     * Whether the user may change their own email address. It is false when something else manages it,
     * e.g. their organization's identity provider.
     */
    public function canChangeEmail(User $user): bool
    {
        return true;
    }
}
