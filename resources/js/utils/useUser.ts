import { usePage } from '@inertiajs/vue3';
import type { User } from '@/types/models';

const page = usePage<{
    auth: {
        user: User;
    };
}>();
function getCurrentUserId() {
    return page.props.auth.user.id;
}

function getCurrentOrganizationId() {
    return page.props.auth.user.current_team_id;
}

function getCurrentMembershipId() {
    return page.props.auth.user.all_teams.find(
        (team) => team.id === getCurrentOrganizationId()
    )?.membership.id;
}

function getCurrentRole() {
    return page.props.auth.user.all_teams.find(
        (team) => team.id === getCurrentOrganizationId()
    )?.membership.role;
}

export {
    getCurrentOrganizationId,
    getCurrentUserId,
    getCurrentMembershipId,
    getCurrentRole,
};
