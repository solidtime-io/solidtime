import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import checker from 'vite-plugin-checker';
import {
    collectModuleAssetsPaths,
    collectModulePlugins,
} from './vite-module-loader.js';

async function getConfig() {
    const paths = ['resources/js/app.ts', 'resources/css/app.css', 'resources/css/filament/admin/theme.css'];
    const modulePaths = await collectModuleAssetsPaths('extensions');
    const additionalPlugins = await collectModulePlugins('extensions');

    return defineConfig({
        build: {
            sourcemap: true, // Source map generation must be turned on
        },
        plugins: [
            laravel({
                input: [...paths, ...modulePaths],
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
            ...additionalPlugins,
        ],
        server: {
            host: true,
            hmr: {
                host: process.env.VITE_HOST_NAME,
                clientPort: 80,
            },
        },
    });
}

export default getConfig();
