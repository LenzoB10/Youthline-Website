import 'vue3-toastify/dist/index.css';
import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createPinia } from 'pinia';
import { createApp, h } from 'vue';
import Vue3Toastify from 'vue3-toastify';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import can from './Directives/can';
import clickOutside from './Directives/clickOutside';
import reveal from './Directives/reveal';
import { useThemeStore } from './Stores/theme';

const appName = import.meta.env.VITE_APP_NAME || 'Youthline';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const pinia = createPinia();
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(pinia)
            .use(ZiggyVue)
            .use(Vue3Toastify);

        // Initialize theme
        const theme = useThemeStore();
        theme.initializeTheme();

        app.directive('can', can);
        app.directive('click-outside', clickOutside);
        app.directive('reveal', reveal);

        app.mount(el);

        return app;
    },
    progress: {
        color: '#55B166',
    },
});
