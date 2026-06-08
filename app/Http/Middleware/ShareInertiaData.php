<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Models\User;
use App\Service\PermissionStore;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\MessageBag;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use Symfony\Component\HttpFoundation\Response;

class ShareInertiaData
{
    /**
     * Handle the incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var PermissionStore $permissions */
        $permissions = app(PermissionStore::class);
        Inertia::share([
            'auth' => [
                'permissions' => $request->user() !== null && $request->user()->currentOrganization !== null ? $permissions->getPermissions($request->user()->currentOrganization) : [],
                'user' => function () use ($request): array {
                    /** @var User|null $user */
                    $user = $request->user();

                    if ($user === null) {
                        return [];
                    }

                    $currentOrganization = $user->currentOrganization;

                    return array_merge([
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'email_verified_at' => $user->email_verified_at,
                        'current_team_id' => $user->current_team_id,
                        'profile_photo_path' => $user->profile_photo_path,
                        'timezone' => $user->timezone,
                        'week_start' => $user->week_start,
                        'profile_photo_url' => $user->profile_photo_url,
                        'two_factor_enabled' => Features::enabled(Features::twoFactorAuthentication())
                            && ! is_null($user->two_factor_secret),
                        'current_team' => $currentOrganization !== null ? [
                            'id' => $currentOrganization->id,
                            'user_id' => $currentOrganization->user_id,
                            'name' => $currentOrganization->name,
                            'personal_team' => $currentOrganization->personal_team,
                            'currency' => $currentOrganization->currency,
                        ] : null,
                    ], array_filter([
                        'all_teams' => $user->organizations->map(function (Organization $organization): array {
                            return [
                                'id' => $organization->id,
                                'name' => $organization->name,
                                'personal_team' => $organization->personal_team,
                                'currency' => $organization->currency,
                                'membership' => [
                                    'role' => $organization->membership->role,
                                    'id' => $organization->membership->id,
                                ],
                            ];
                        })->all(),
                    ]));
                },
            ],
            'errorBags' => function () {
                /** @var array<string, MessageBag>|null $bags */
                $bags = Session::get('errors')?->getBags();
                $bagsCollection = collect($bags ?: []);

                return $bagsCollection->mapWithKeys(function (MessageBag $bag, string $key) {
                    return [$key => $bag->messages()];
                })->all();
            },
        ]);

        return $next($request);
    }
}
