<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Service\PermissionStore;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use LogicException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rejects requests to organization routes when the authenticated user is not a member of the organization.
 * Routes without an organization parameter are passed through.
 */
class CheckOrganizationMember
{
    public function __construct(private readonly PermissionStore $permissionStore) {}

    /**
     * @param  Closure(Request): (Response)  $next
     *
     * @throws AuthorizationException
     */
    public function handle(Request $request, Closure $next): Response
    {
        $organization = $request->route('organization');

        if ($organization === null) {
            return $next($request);
        }

        if (! ($organization instanceof Organization)) {
            throw new LogicException('The organization must be loaded before this middleware.');
        }

        if (! $this->permissionStore->isMember($organization)) {
            throw new AuthorizationException;
        }

        return $next($request);
    }
}
