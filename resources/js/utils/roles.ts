import type { Role } from '@/types/jetstream';

export function filterRoles(roles: Role[]) {
    return roles.filter(function (role) {
        return role.key !== 'placeholder' && role.key !== 'owner';
    });
}
