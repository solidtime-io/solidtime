<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Models\Organization;
use Brick\Money\Currency;
use Brick\Money\ISOCurrencyProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    /**
     * Show the team creation screen.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Teams/Create');
    }

    /**
     * Show the organizatio details screen.
     *
     * @param  string  $organizationId  The organization ID
     */
    public function show(string $organizationId): Response|RedirectResponse
    {
        $organization = Str::isUuid($organizationId) ? Organization::find($organizationId) : null;
        if ($organization === null) {
            return redirect()->route('dashboard');
        }
        if (! $this->hasPermission($organization, 'organizations:view')) {
            return redirect()->route('dashboard');
        }

        $owner = $organization->owner;

        return Inertia::render('Teams/Show', [
            'team' => [
                'id' => $organization->getKey(),
                'name' => $organization->name,
                'currency' => $organization->currency,
                'owner' => [
                    'id' => $owner->getKey(),
                    'name' => $owner->name,
                    'profile_photo_url' => $owner->profile_photo_url,
                ],
            ],
            'currencies' => array_map(function (Currency $currency): string {
                return $currency->getName();
            }, ISOCurrencyProvider::getInstance()->getAvailableCurrencies()),
            'permissions' => [
                'canDeleteTeam' => $this->hasPermission($organization, 'organizations:delete'),
                'canUpdateTeam' => $this->hasPermission($organization, 'organizations:update'),
            ],
        ]);
    }
}
