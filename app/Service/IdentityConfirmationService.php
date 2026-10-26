<?php

declare(strict_types=1);

namespace App\Service;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Confirms the account owner before sensitive actions like deleting an account or organization.
 * Users confirm by re-entering their password. Users without one (or with one they may not log in with)
 * can't confirm, so an administrator has to do these actions for them.
 */
class IdentityConfirmationService
{
    /**
     * Whether the user may confirm with their local password: they need one, and it must be one
     * they may log in with. A password should not be an easier proof of identity than logging in.
     */
    public function confirmsWithPassword(User $user): bool
    {
        return $user->hasLocalPassword() && app(AuthenticationContract::class)->isPasswordLoginAllowed($user);
    }

    /**
     * Whether the given password confirms the user's identity.
     */
    public function confirmPassword(User $user, ?string $password): bool
    {
        return $password !== null
            && $this->confirmsWithPassword($user)
            && Hash::check($password, (string) $user->password);
    }
}
