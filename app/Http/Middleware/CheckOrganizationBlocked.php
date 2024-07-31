<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\Api\OrganizationHasNoSubscriptionButMultipleMembersException;
use App\Models\Organization;
use App\Service\BillingContract;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckOrganizationBlocked
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     *
     * @throws OrganizationHasNoSubscriptionButMultipleMembersException
     */
    public function handle(Request $request, Closure $next): Response
    {
        $organization = $request->route('organization');

        if (! ($organization instanceof Organization)) {
            throw new \LogicException('The organization must be loaded before this middleware.');
        }

        /** @var BillingContract $billing */
        $billing = app(BillingContract::class);

        if ($billing->isBlocked($organization)) {
            throw new OrganizationHasNoSubscriptionButMultipleMembersException;
        }

        return $next($request);
    }
}
