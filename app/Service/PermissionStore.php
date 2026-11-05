<?php

declare(strict_types=1);

namespace App\Service;

use App\Enums\Role;
use App\Models\Member;
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
                'goals:view:own',
                'goals:create:own',
                'goals:update:own',
                'goals:delete:own',
                'goals:view:organization-type',
                'goals:create:organization-type',
                'goals:update:organization-type',
                'goals:delete:organization-type',
                'invoices:view',
                'invoices:create',
                'invoices:update',
                'invoices:download',
                'invoices:delete',
                'invoice-recipients:view',
                'invoice-recipients:create',
                'invoice-recipients:update',
                'invoice-recipients:delete',
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
                'goals:view:own',
                'goals:create:own',
                'goals:update:own',
                'goals:delete:own',
                'goals:view:organization-type',
                'goals:create:organization-type',
                'goals:update:organization-type',
                'goals:delete:organization-type',
                'invoices:view',
                'invoices:create',
                'invoices:update',
                'invoices:download',
                'invoices:delete',
                'invoice-recipients:view',
                'invoice-recipients:create',
                'invoice-recipients:update',
                'invoice-recipients:delete',
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
                'goals:view:own',
                'goals:create:own',
                'goals:update:own',
                'goals:delete:own',
                'goals:view:organization-type',
                'goals:create:organization-type',
                'goals:update:organization-type',
                'goals:delete:organization-type',
                'invoices:view',
                'invoices:create',
                'invoices:update',
                'invoices:download',
                'invoices:delete',
                'invoice-recipients:view',
                'invoice-recipients:create',
                'invoice-recipients:update',
                'invoice-recipients:delete',
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
                'goals:view:own',
                'goals:create:own',
                'goals:update:own',
                'goals:delete:own',
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

    /**
     * @var array<string, Member>
     */
    private array $memberCache = [];

    public function clear(): void
    {
        $this->permissionCache = [];
        $this->memberCache = [];
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
        return in_array($permission, $this->getPermissionsByUser($organization, $user), true);
    }

    /**
     * Whether the currently authenticated user is a member of the organization.
     * Warms the permission cache, so later permission checks in the same request need no query.
     */
    public function isMember(Organization $organization): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user === null) {
            return false;
        }

        return $this->getMember($organization, $user) !== null;
    }

    /**
     * @return array<string>
     */
    private function getPermissionsByUser(Organization $organization, User $user): array
    {
        $cacheKey = $user->getKey().'|'.$organization->getKey();
        if (isset($this->permissionCache[$cacheKey])) {
            return $this->permissionCache[$cacheKey];
        }

        $member = $this->getMember($organization, $user);
        if ($member === null) {
            // Not cached, so a membership created later in the same request is picked up
            return [];
        }
        $role = $member->role;

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

        $this->permissionCache[$cacheKey] = $permissions;

        return $permissions;
    }

    /**
     * The membership of the user in the organization, cached for the rest of the request.
     */
    public function getMember(Organization $organization, User $user): ?Member
    {
        $cacheKey = $user->getKey().'|'.$organization->getKey();
        if (isset($this->memberCache[$cacheKey])) {
            return $this->memberCache[$cacheKey];
        }

        /** @var Member|null $member */
        $member = Member::query()
            ->whereBelongsTo($organization, 'organization')
            ->whereBelongsTo($user, 'user')
            ->first();

        if ($member !== null) {
            $this->memberCache[$cacheKey] = $member;
        }

        return $member;
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
