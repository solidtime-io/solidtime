import { router } from '@inertiajs/vue3';
import { initializeStores } from '@/utils/init';

export function switchOrganization(organizationId: string) {
    router.put(
        route('current-team.update'),
        {
            team_id: organizationId,
        },
        {
            preserveState: false,
            onSuccess: () => {
                initializeStores();
            },
        }
    );
}
