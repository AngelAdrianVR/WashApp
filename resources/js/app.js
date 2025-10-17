import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

//primevue
import PrimeVue from 'primevue/config';
import Aura from '@primeuix/themes/aura';
import ToastService from 'primevue/toastservice';
import Chart from 'primevue/chart';
import 'primeicons/primeicons.css';
import ConfirmationService from 'primevue/confirmationservice'; 

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            // Configuración de PrimeVue con el tema Aura y modo oscuro
            .use(PrimeVue, {
                ripple: true,
                theme: {
                    preset: Aura,
                    options: {
                        // Conecta el modo oscuro de PrimeVue con el de Tailwind CSS
                        darkModeSelector: '.dark',
                    }
                }
            })
            .component('Chart', Chart)
            .use(ConfirmationService)
            .use(ToastService)
            .mount(el);
    },
    progress: {
        color: '#2FCBFD',
    },
});
