import { defineConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';
import { fileURLToPath } from 'node:url';

export default defineConfig({
    plugins: [vue()],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    test: {
        environment: 'happy-dom',
        globals: false,
        setupFiles: ['./resources/js/test-setup.ts'],
        include: ['resources/js/**/*.{test,spec}.{ts,tsx}'],
        exclude: ['**/node_modules/**', '**/e2e/**', '**/dist/**'],
    },
});
