import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';
import { solidtimeTheme } from './resources/js/packages/ui/tailwind.theme.js';

/** @type {import("tailwindcss").Config} */
export default {
    darkMode: ['selector', '.dark'],
    content: [
        './extensions/Invoicing/resources/js/**/*.vue',
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.ts',
    ],
    theme: {
        extend: {
            ...solidtimeTheme,
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [
        forms,
        typography,
        require('@tailwindcss/container-queries'),
        require('tailwindcss-animate'),
    ],
};
