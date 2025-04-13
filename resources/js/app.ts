import './bootstrap';
import '../css/app.css';
import { createApp, h } from "vue";
import { createInertiaApp, usePage } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { createPinia } from 'pinia';
import type { User } from '@/types/models';
import { VueQueryPlugin } from '@tanstack/vue-query';
import { type DefineComponent } from 'vue';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const pinia = createPinia();

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>{
            if(name.includes('Invoicing::')){
                const [module, page] = name.split('::');

                const pagePath = module
                    ? `../../extensions/${module}/resources/js/Pages/${page}.vue`
                    : `./Pages/${page}.vue`;

                // BillingPortal is a Vue 2 Component and therefore should not be imported
                const pages = module
                    ? import.meta.glob<DefineComponent>(['../../extensions/**/resources/js/Pages/*.vue', '!**/BillingPortal.vue'])
                    : import.meta.glob<DefineComponent>('./Pages/**/*.vue');

                return resolvePageComponent(
                    pagePath,
                    pages)
            }
            else {
                return resolvePageComponent(
                    `./Pages/${name}.vue`,
                    import.meta.glob<DefineComponent>('./Pages/**/*.vue'));
            }

    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });

        // currently only one vue app setup hook is supported
        if (window.vueAppSetupHook) {
            window.vueAppSetupHook(app);
        }
        window.getWeekStartSetting = function () {
            const page = usePage<{
                auth: {
                    user: User;
                };
            }>();
            return page.props.auth.user.week_start ?? 'monday';
        };
        window.getTimezoneSetting = function () {
            const page = usePage<{
                auth: {
                    user: User;
                };
            }>();
            return page.props.auth.user.timezone;
        };

        app.use(plugin).use(pinia).use(ZiggyVue).use(VueQueryPlugin).mount(el);
    },

    progress: {
        color: '#4B5563',
    },
});
