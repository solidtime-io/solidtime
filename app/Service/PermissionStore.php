<?php

declare(strict_types=1);

namespace App\Service;

use App\Enums\Role;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class PermissionStore
{
    /**
     * @var array<string, array{name: string, permissions: array<string>, description: string}>
     */
    private const array ROLE_DEFINITIONS = [
        'owner' => [
            'name' => 'Owner',
            'permissions' => [
                'charts:view:own',
                'charts:view:all',
                'projects:view',
                'projects:view:all',
                'projects:create',
                'projects:update',
                'projects:delete',
                'project-members:view',
                'project-members:create',
                'project-members:update',
                'project-members:delete',
                'tasks:view',
                'tasks:view:all',
                'tasks:create',
                'tasks:create:all',
                'tasks:update',
                'tasks:update:all',
                'tasks:delete',
                'tasks:delete:all',
                'time-entries:view:all',
                'time-entries:create:all',
                'time-entries:update:all',
                'time-entries:delete:all',
                'time-entries:view:own',
                'time-entries:create:own',
                'time-entries:update:own',
                'time-entries:delete:own',
                'tags:view',
                'tags:create',
                'tags:update',
                'tags:delete',
                'clients:view',
                'clients:view:all',
                'clients:create',
                'clients:update',
                'clients:delete',
                'organizations:view',
                'organizations:update',
                'organizations:delete',
                'import',
                'export',
                'invitations:view',
                'invitations:create',
                'invitations:resend',
                'invitations:remove',
                'members:view',
                'members:invite-placeholder',
                'members:change-ownership',
                'members:make-placeholder',
                'members:merge-into',
                'members:update',
                'members:delete',
                'billing',
                'reports:view',
                'reports:create',
                'reports:update',
                'reports:delete',
                'invoices:view',
                'invoices:create',
                'invoices:update',
                'invoices:download',
                'invoices:delete',
                'invoice-settings:view',
                'invoice-settings:update',
            ],
            'description' => 'Owner users can perform any action. There is only one owner per organization.',
        ],
        'admin' => [
            'name' => 'Administrator',
            'permissions' => [
                'charts:view:own',
                'charts:view:all',
                'projects:view',
                'projects:view:all',
                'projects:create',
                'projects:update',
                'projects:delete',
                'project-members:view',
                'project-members:create',
                'project-members:update',
                'project-members:delete',
                'tasks:view',
                'tasks:view:all',
                'tasks:create',
                'tasks:create:all',
                'tasks:update',
                'tasks:update:all',
                'tasks:delete',
                'tasks:delete:all',
                'time-entries:view:all',
                'time-entries:create:all',
                'time-entries:update:all',
                'time-entries:delete:all',
                'time-entries:view:own',
                'time-entries:create:own',
                'time-entries:update:own',
                'time-entries:delete:own',
                'tags:view',
                'tags:create',
                'tags:update',
                'tags:delete',
                'clients:view',
                'clients:view:all',
                'clients:create',
                'clients:update',
                'clients:delete',
                'organizations:view',
                'organizations:update',
                'import',
                'export',
                'invitations:view',
                'invitations:create',
                'invitations:resend',
                'invitations:remove',
                'members:view',
                'members:invite-placeholder',
                'members:make-placeholder',
                'members:merge-into',
                'members:delete',
                'members:update',
                'reports:view',
                'reports:create',
                'reports:update',
                'reports:delete',
                'invoices:view',
                'invoices:create',
                'invoices:update',
                'invoices:download',
                'invoices:delete',
                'invoice-settings:view',
                'invoice-settings:update',
            ],
            'description' => 'Administrator users can perform any action, except accessing the billing dashboard.',
        ],
        'manager' => [
            'name' => 'Manager',
            'permissions' => [
                'charts:view:own',
                'charts:view:all',
                'projects:view',
                'projects:view:all',
                'projects:create',
                'projects:update',
                'projects:delete',
                'project-members:view',
                'project-members:create',
                'project-members:update',
                'project-members:delete',
                'tasks:view',
                'tasks:view:all',
                'tasks:create',
                'tasks:create:all',
                'tasks:update',
                'tasks:update:all',
                'tasks:delete',
                'tasks:delete:all',
                'time-entries:view:all',
                'time-entries:create:all',
                'time-entries:update:all',
                'time-entries:delete:all',
                'time-entries:view:own',
                'time-entries:create:own',
                'time-entries:update:own',
                'time-entries:delete:own',
                'tags:view',
                'tags:create',
                'tags:update',
                'tags:delete',
                'clients:view',
                'clients:view:all',
                'clients:create',
                'clients:update',
                'clients:delete',
                'organizations:view',
                'invitations:view',
                'members:view',
                'reports:view',
                'reports:create',
                'reports:update',
                'reports:delete',
                'invoices:view',
                'invoices:create',
                'invoices:update',
                'invoices:download',
                'invoices:delete',
                'invoice-settings:view',
                'invoice-settings:update',
            ],
            'description' => 'Managers have full access to all projects, time entries, ect. but cannot manage the organization (add/remove member, edit the organization, ect.).',
        ],
        'employee' => [
            'name' => 'Employee',
            'permissions' => [
                'charts:view:own',
                'projects:view',
                'tags:view',
                'tasks:view',
                'clients:view',
                'time-entries:view:own',
                'time-entries:create:own',
                'time-entries:update:own',
                'time-entries:delete:own',
                'organizations:view',
            ],
            'description' => 'Employees have the ability to read, create, and update their own time entries, they can see the projects that they are members of and the clients they are assigned to.',
        ],
        'placeholder' => [
            'name' => 'Placeholder',
            'permissions' => [],
            'description' => 'Placeholders are used for importing data. They cannot log in and have no permissions.',
        ],
    ];

    /**
     * @var array<string, array<string>>
     */
    private static array $customRolePermissions = [];

    /**
     * @var array<string, array<string>>
     */
    private array $permissionCache = [];

    public function clear(): void
    {
        $this->permissionCache = [];
    }

    /**
     * @return array<string, array{name: string, permissions: array<string>, description: string}>
     */
    public static function roleDefinitions(): array
    {
        return self::ROLE_DEFINITIONS;
    }

    /**
     * @param  array<string>  $permissions
     */
    public static function registerCustomRole(string $role, array $permissions): void
    {
        self::$customRolePermissions[$role] = $permissions;
    }

    public static function resetCustomRoles(): void
    {
        self::$customRolePermissions = [];
    }

    /**
     * @return array<string>
     */
    public static function permissionsForRole(string $role): array
    {
        return self::$customRolePermissions[$role]
            ?? self::ROLE_DEFINITIONS[$role]['permissions']
            ?? [];
    }

    public function has(Organization $organization, string $permission): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user === null) {
            return false;
        }

        return $this->userHas($organization, $user, $permission);
    }

    public function userHas(Organization $organization, User $user, string $permission): bool
    {
        if (! isset($this->permissionCache[$user->getKey().'|'.$organization->getKey()])) {
            if (! $user->isMemberOfOrganization($organization)) {
                return false;
            }

            $permissions = $this->getPermissionsByUser($organization, $user);
            $this->permissionCache[$user->getKey().'|'.$organization->getKey()] = $permissions;
        } else {
            $permissions = $this->permissionCache[$user->getKey().'|'.$organization->getKey()];
        }

        return in_array($permission, $permissions, true);
    }

    /**
     * @return array<string>
     */
    private function getPermissionsByUser(Organization $organization, User $user): array
    {
        if (! $user->isMemberOfOrganization($organization)) {
            return [];
        }

        $role = $organization->users
            ->where('id', $user->getKey())
            ->first()
            ?->membership
            ?->role;

        if ($role === null) {
            return [];
        }

        $permissions = self::permissionsForRole($role);

        // If the organization allows employees to manage tasks and the user is an employee,
        // add the task management permissions for accessible projects
        if ($role === Role::Employee->value && $organization->employees_can_manage_tasks) {
            $permissions = array_merge($permissions, [
                'tasks:create',
                'tasks:update',
                'tasks:delete',
            ]);
        }

        return $permissions;
    }

    /**
     * @return array<string>
     */
    public function getPermissions(Organization $organization): array
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user === null) {
            return [];
        }

        return $this->getPermissionsByUser($organization, $user);
    }
}
