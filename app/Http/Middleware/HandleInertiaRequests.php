<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Service\BillingContract;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Nwidart\Modules\Facades\Module;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Defines the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $hasBilling = Module::has('Billing') && Module::isEnabled('Billing');

        /** @var BillingContract $billing */
        $billing = app(BillingContract::class);

        $currentOrganization = $request->user()?->currentTeam;

        return array_merge(parent::share($request), [
            'has_billing_extension' => $hasBilling,
            'billing' => $billing !== null && $currentOrganization !== null ? [
                'has_subscription' => $billing->hasSubscription($currentOrganization),
                'has_trial' => $billing->hasTrial($currentOrganization),
                'is_blocked' => $billing->isBlocked($currentOrganization),
            ] : null,
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
            ],
        ]);
    }
}
