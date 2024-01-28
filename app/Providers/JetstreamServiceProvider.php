<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\Jetstream\AddOrganizationMember;
use App\Actions\Jetstream\CreateOrganization;
use App\Actions\Jetstream\DeleteOrganization;
use App\Actions\Jetstream\DeleteUser;
use App\Actions\Jetstream\InviteOrganizationMember;
use App\Actions\Jetstream\RemoveOrganizationMember;
use App\Actions\Jetstream\UpdateOrganization;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use Illuminate\Support\ServiceProvider;
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
        Jetstream::useTeamInvitationModel(OrganizationInvitation::class);
    }

    /**
     * Configure the roles and permissions that are available within the application.
     */
    protected function configurePermissions(): void
    {
        Jetstream::defaultApiTokenPermissions([]);

        Jetstream::role('admin', 'Administrator', [
            'projects:view',
            'projects:create',
            'projects:update',
            'projects:delete',
        ])->description('Administrator users can perform any action.');

        Jetstream::role('manager', 'Manager', [
            'projects:view',
            'projects:create',
            'projects:update',
            'projects:delete',
        ])->description('Editor users have the ability to read, create, and update.');

        Jetstream::role('employee', 'Employee', [
            'projects:view',
            'projects:create',
            'projects:update',
            'projects:delete',
        ])->description('Editor users have the ability to read, create, and update.');
    }
}
