<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Exceptions\MovedToApiException;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Validate and update the given user's profile information.
     *
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function update(User $user, array $input): void
    {
        throw new MovedToApiException;
    }
}
