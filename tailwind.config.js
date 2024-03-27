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
            fontFamily: {
                sans: ['Outfit', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                'white': '#D9DCFB',
                'default-background': '#040618',
                'default-background-seperator': '#13152B',
                'card-background': 'var(--theme-color-card-background)',
                'card-background-active': '#1C1E34',
                'card-background-seperator': '#262A51',
                'card-border': '#242940',
                'card-border-active': '#2A3461',
                'muted': '#8F93B7',
                'icon-default': 'var(--theme-color-icon-default)',
                'icon-active': '#787DA8',
                'menu-active': '#13152B',
                'input-placeholder': '#42466C',
                'input-border': '#242740',
                'input-border-active': '#797EA8',
                'input-background': '#030513',
                'button-secondary-background': '#22243E',
                'button-secondary-background-hover': '#292C4D',
                'button-secondary-border': '#353961',
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
