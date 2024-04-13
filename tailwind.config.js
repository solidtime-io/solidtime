import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            containers: {
                '2xs': '16rem',
            },
            fontFamily: {
                sans: ['Outfit', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                'white': '#D9DCFB',
                'default-background': 'var(--theme-color-default-background)',
                'default-background-separator': '#13152B',
                'card-background': 'var(--theme-color-card-background)',
                'card-background-active':
                    'var(--theme-color-card-background-active)',
                'card-background-separator': '#262A51',
                'card-border': 'var(--theme-color-card-border)',
                'card-border-active': 'var(--theme-color-card-border-active)',
                'muted': '#8F93B7',
                'icon-default': 'var(--theme-color-icon-default)',
                'tab-background': 'var(--theme-color-tab-background)',
                'tab-background-active':
                    'var(--theme-color-tab-background-active)',
                'tab-border': 'var(--theme-color-tab-border)',
                'icon-active': '#787DA8',
                'menu-active': '#13152B',
                'input-placeholder': '#42466C',
                'input-border': '#242740',
                'input-border-active': '#797EA8',
                'input-background': '#030513',
                'button-secondary-background':
                    'var(--theme-color-card-background)',
                'button-secondary-background-hover':
                    'var(--theme-color-card-background-active)',
                'button-secondary-border': 'var(--theme-color-card-border)',
                'row-separator': 'var(--theme-color-row-separator-background)',
                'row-heading-background':
                    'var(--theme-color-row-heading-background)',
                'row-heading-border': 'var(--theme-color-row-heading-border)',
                'accent': {
                    '50': '#eff7ff',
                    '100': '#daecff',
                    '200': '#b0d7ff',
                    '300': '#91caff',
                    '400': '#5eadfc',
                    '500': '#388bf9',
                    '600': '#226cee',
                    '700': '#1a57db',
                    '800': '#1c46b1',
                    '900': '#1c3f8c',
                    '950': '#162755',
                },
            },
        },
    },

    plugins: [forms, typography, require('@tailwindcss/container-queries')],
};
