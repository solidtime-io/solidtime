import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export interface Whitelabel {
    name: string;
    logo_url: string | null;
    dark_logo_url: string | null;
}

const defaultWhitelabel: Whitelabel = {
    name: 'solidtime',
    logo_url: null,
    dark_logo_url: null,
};

export function getWhitelabel(): Whitelabel {
    const page = usePage<{
        whitelabel?: Whitelabel;
    }>();

    return page.props.whitelabel ?? defaultWhitelabel;
}

export function useWhitelabel() {
    return computed(() => getWhitelabel());
}
