import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import checker from 'vite-plugin-checker';
import path from 'path';

export default defineConfig({
    resolve: {
        alias: {
            'ziggy-js': path.resolve('vendor/tightenco/ziggy'),
        },
    },
    plugins: [
        laravel({
            input: 'resources/js/app.ts',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        checker({
            // e.g. use TypeScript check
            typescript: true,
            vueTsc: true,
            lintCommand: 'eslint "./**/*.{ts,vue}"',
        }),
    ],
    server: {
        host: true,
        hmr: {
            host: process.env.VITE_HOST_NAME,
            clientPort: 80,
        },
    },
});
