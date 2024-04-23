import { PageProps as InertiaPageProps } from '@inertiajs/core';
import { AxiosInstance } from 'axios';
import ziggyRoute from 'ziggy-js';
import { PageProps as AppPageProps } from './';
import type { App } from 'vue';

declare global {
    interface Window {
        axios: AxiosInstance;
        initialDataLoaded: boolean;
        vueAppSetupHook?: (app: App) => void;
    }

    let route: typeof ziggyRoute;
}

declare module 'vue' {
    interface ComponentCustomProperties {
        route: typeof ziggyRoute;
    }
}

declare module '@inertiajs/core' {
    interface PageProps extends InertiaPageProps, AppPageProps {}
}
