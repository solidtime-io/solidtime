<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\Jetstream\AddOrganizationMember;
use App\Actions\Jetstream\CreateOrganization;
use App\Actions\Jetstream\DeleteOrganization;
use App\Actions\Jetstream\DeleteUser;
use App\Actions\Jetstream\InviteOrganizationMember;
use App\Actions\Jetstream\RemoveOrganizationMember;
use App\Actions\Jetstream\UpdateMemberRole;
use App\Actions\Jetstream\UpdateOrganization;
use App\Actions\Jetstream\ValidateOrganizationDeletion;
use App\Enums\Weekday;
use App\Models\Member;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Service\PermissionStore;
use App\Service\TimezoneService;
use Brick\Money\Currency;
use Brick\Money\ISOCurrencyProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Jetstream\Actions\UpdateTeamMemberRole;
use Laravel\Jetstream\Actions\ValidateTeamDeletion;
use Laravel\Jetstream\Jetstream;

class JetstreamServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configurePermissions();

        Jetstream::createTeamsUsing(CreateOrganization::class);
        Jetstream::updateTeamNamesUsing(UpdateOrganization::class);
        Jetstream::addTeamMembersUsing(AddOrganizationMember::class);
        Jetstream::inviteTeamMembersUsing(InviteOrganizationMember::class);
        Jetstream::removeTeamMembersUsing(RemoveOrganizationMember::class);
        Jetstream::deleteTeamsUsing(DeleteOrganization::class);
        Jetstream::deleteUsersUsing(DeleteUser::class);
        Jetstream::useTeamModel(Organization::class);
        Jetstream::useMembershipModel(Member::class);
        Jetstream::useTeamInvitationModel(OrganizationInvitation::class);
        app()->singleton(UpdateTeamMemberRole::class, UpdateMemberRole::class);
        app()->singleton(ValidateTeamDeletion::class, ValidateOrganizationDeletion::class);
        Gate::define('removeTeamMember', function (User $user, Organization $team) {
            return false;
        });
    }

    /**
     * Configure the roles and permissions that are available within the application.
     */
    protected function configurePermissions(): void
    {
        Jetstream::defaultApiTokenPermissions([]);

        foreach (PermissionStore::roleDefinitions() as $role => $definition) {
            Jetstream::role($role, $definition['name'], $definition['permissions'])
                ->description($definition['description']);
        }

        Jetstream::inertia()
            ->whenRendering(
                'Profile/Show',
                function (Request $request, array $data): array {
                    return array_merge($data, [
                        'timezones' => $this->app->get(TimezoneService::class)->getSelectOptions(),
                        'weekdays' => Weekday::toSelectArray(),
                    ]);
                }
            )
            ->whenRendering(
                'Teams/Show',
                function (Request $request, array $data): array {
                    /** @var Organization $teamModel */
                    $teamModel = $data['team'];
                    $owner = $teamModel->owner;

                    return array_merge($data, [
                        'team' => [
                            'id' => $teamModel->getKey(),
                            'name' => $teamModel->name,
                            'currency' => $teamModel->currency,
                            'owner' => [
                                'id' => $owner->getKey(),
                                'name' => $owner->name,
                                'profile_photo_url' => $owner->profile_photo_url,
                            ],
                        ],
                        'currencies' => array_map(function (Currency $currency): string {
                            return $currency->getName();
                        }, ISOCurrencyProvider::getInstance()->getAvailableCurrencies()),
                    ]);
                }
            );
    }
}
