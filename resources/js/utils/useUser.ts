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

function getWeekStart() {
    const weekStart = window?.getWeekStartSetting() as string;

    if (!weekStart) {
        throw new Error(
            'Please make sure to provide the current user week start setting as a vue inject (week_start)'
        );
    }
    return weekStart;
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

function getUserTimezone() {
    const timezone = window?.getTimezoneSetting() as string;
    if (!timezone) {
        throw new Error(
            'Please make sure to provide the current user timezone as a vue inject (timezone)'
        );
    }
    return timezone;
}

export {
    getCurrentOrganizationId,
    getCurrentUserId,
    getUserTimezone,
    getWeekStart,
    getCurrentMembershipId,
    getCurrentRole,
};
