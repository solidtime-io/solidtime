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

export { getCurrentOrganizationId, getCurrentUserId };
