<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\User\UserResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Resources\Json\JsonResource;

class UserController extends Controller
{
    /**
     * Get the current user
     *
     * This endpoint is independent of organization.
     *
     * @operationId getMe
     *
     * @throws AuthorizationException
     */
    public function me(): JsonResource
    {
        $user = $this->user();

        return new UserResource($user);
    }
}
