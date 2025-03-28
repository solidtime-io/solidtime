import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import("tailwindcss").Config} */
export default {
    darkMode: 'selector',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],
    theme: {
        extend: {
            boxShadow: {
                'card': 'var(--theme-shadow-card)',
                'dropdown': 'var(--theme-shadow-dropdown)',
            },
            containers: {
                '2xs': '16rem',
            },
            fontFamily: {
                sans: ['Outfit', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                'ring': 'var(--theme-color-ring)',
                'primary': 'var(--color-bg-primary)',
                'secondary': 'var(--color-bg-secondary)',
                'tertiary': 'var(--color-bg-tertiary)',
                'quaternary': 'var(--color-bg-quaternary)',
                'background': 'var(--color-bg-background)',
                'text-primary': 'var(--color-text-primary)',
                'text-secondary': 'var(--color-text-secondary)',
                'text-tertiary': 'var(--color-text-tertiary)',
                'text-quaternary': 'var(--color-text-quaternary)',
                'border-primary': 'var(--color-border-primary)',
                'border-secondary': 'var(--color-border-secondary)',
                'border-tertiary': 'var(--color-border-tertiary)',
                'default-background': 'var(--theme-color-default-background)',
                'default-background-separator':
                    'var(--theme-color-default-background-separator)',
                'row-background': 'var(--theme-color-row-background)',
                'card-background': 'var(--theme-color-card-background)',
                'card-background-active':
                    'var(--theme-color-card-background-active)',
                'card-background-separator':
                    'var(--theme-color-card-background-separator)',
                'card-border': 'var(--theme-color-card-border)',
                'card-border-active': 'var(--theme-color-card-border-active)',
                'muted': 'var(--theme-color-muted-text)',
                'tab-background': 'var(--theme-color-tab-background)',
                'tab-background-active':
                    'var(--theme-color-tab-background-active)',
                'tab-border': 'var(--theme-color-tab-border)',
                'icon-default': 'var(--theme-color-icon-default)',
                'icon-active': 'var(--theme-color-icon-active)',
                'menu-active': 'var(--theme-color-menu-active)',
                'input-border': 'var(--theme-color-input-border)',
                'input-border-active': 'var(--color-input-border-active)',
                'input-background': 'var(--theme-color-input-background)',
                'button-secondary-background':
                    'var(--theme-button-secondary-background)',
                'button-secondary-background-hover':
                    'var(--theme-button-secondary-background-active)',
                'button-secondary-border': 'var(--theme-color-card-border)',
                'row-separator': 'var(--theme-color-row-separator-background)',
                'row-heading-background':
                    'var(--theme-color-row-heading-background)',
                'row-heading-border': 'var(--theme-color-row-heading-border)',
                'accent': {
                    '50': 'rgba(var(--color-accent-50), <alpha-value>)',
                    '100': 'rgba(var(--color-accent-100), <alpha-value>)',
                    '200': 'rgba(var(--color-accent-200), <alpha-value>)',
                    '300': 'rgba(var(--color-accent-300), <alpha-value>)',
                    '400': 'rgba(var(--color-accent-400), <alpha-value>)',
                    '500': 'rgba(var(--color-accent-500), <alpha-value>)',
                    '600': 'rgba(var(--color-accent-600), <alpha-value>)',
                    '700': 'rgba(var(--color-accent-700), <alpha-value>)',
                    '800': 'rgba(var(--color-accent-800), <alpha-value>)',
                    '900': 'rgba(var(--color-accent-900), <alpha-value>)',
                    '950': 'rgba(var(--color-accent-950), <alpha-value>)',                },
                'button-primary-background': 'var(--theme-color-button-primary-background)',
                'button-primary-background-hover':
                    'var(--theme-color-button-primary-background-hover)',
                'button-primary-border': 'var(--theme-color-button-primary-border)',
                'button-primary-text': 'var(--theme-color-button-primary-text)',
                'input-select-active':'var(--theme-color-input-select-active)',
                'input-select-active-hover':'var(--theme-color-input-select-active-hover)',
            },
        },
    },

    plugins: [forms, typography, require('@tailwindcss/container-queries')],
};
