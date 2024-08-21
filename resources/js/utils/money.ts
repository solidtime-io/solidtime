import { usePage } from '@inertiajs/vue3';

const page = usePage<{
    auth: {
        user: {
            current_team: {
                currency: string;
            };
        };
    };
}>();

export function getOrganizationCurrencyString() {
    return page.props?.auth?.user?.current_team?.currency ?? 'EUR';
}
