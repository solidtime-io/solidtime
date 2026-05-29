<?php

declare(strict_types=1);

namespace App\Actions\Jetstream;

use App\Exceptions\Api\ApiException;
use App\Models\User;
use App\Service\DeletionService;
use Illuminate\Validation\ValidationException;
use Laravel\Jetstream\Contracts\DeletesUsers;

class DeleteUser implements DeletesUsers
{
    /**
     * Delete the given user.
     *
     * @throws ValidationException
     *
     * @deprecated Use REST endpoint instead
     */
    public function delete(User $user): void
    {
        try {
            app(DeletionService::class)->deleteUser($user);
        } catch (ApiException $exception) {
            throw ValidationException::withMessages([
                'password' => $exception->getTranslatedMessage(),
            ]);
        }
    }
}
