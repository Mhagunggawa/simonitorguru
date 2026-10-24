import { createInertiaApp } from '@inertiajs/vue3';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

let appName = import.meta.env.VITE_APP_NAME || 'Laravel';
if (typeof document !== 'undefined') {
    const pageElement = document.getElementById('app');
    if (pageElement && pageElement.dataset.page) {
        try {
            const page = JSON.parse(pageElement.dataset.page);
            if (page.props?.settings?.nama_aplikasi) {
                appName = page.props.settings.nama_aplikasi;
            }
        } catch (e) {
            console.error('Could not parse initial page data for appName', e);
        }
    }
}

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#4B5563',
    },
});

if (typeof window !== 'undefined') {
    // This will set light / dark mode on page load...
    initializeTheme();

    // This will listen for flash toast data from the server...
    initializeFlashToast();
}
