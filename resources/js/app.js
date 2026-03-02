import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { createVuetify } from 'vuetify';
import { aliases, mdi } from 'vuetify/iconsets/mdi';
import * as components from 'vuetify/components';
import * as directives from 'vuetify/directives';
import '@mdi/font/css/materialdesignicons.css';
import 'vuetify/styles';
import AppHeader from '@/Components/AppHeader.vue';
import AppSubHeader from '@/Components/AppSubHeader.vue';
import '@fontsource/source-sans-3/index.css'

const materialSymbols = {
    component: (props) => h('span', { class: 'material-symbols-outlined' }, props.icon),
};

const vuetify = createVuetify({
    components,
    directives,
    icons: {
        defaultSet: 'mdi',
        aliases,
        sets: {
            mdi,
            ms: materialSymbols,
        },
    },
    defaults: {
        VBtn: { style: 'text-transform: none; letter-spacing: normal;' },
        VTab: { style: 'text-transform: none; letter-spacing: normal;' },
    },
    theme: {
        defaultTheme: 'efomento',
        themes: {
            efomento: {
                colors: {
                    primary: '#008344',
                    secondary: '#ffcc05',
                    subheader: '#485465',
                },
                variables: {
                    'font-family': 'Source Sans 3, sans-serif',
                },
            },
        },
    },
});

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(vuetify);

        app.component('AppHeader', AppHeader);
        app.component('AppSubHeader', AppSubHeader);

        return app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
