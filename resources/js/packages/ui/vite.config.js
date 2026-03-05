import { defineConfig } from 'vite';
import { resolve } from 'path';
import vue from '@vitejs/plugin-vue';
import dts from 'vite-plugin-dts';

// https://vitejs.dev/config/
export default defineConfig({
    plugins: [vue(), dts()],
    build: {
        lib: {
            // src/indext.ts is where we have exported the component(s)
            entry: resolve(__dirname, 'src/index.ts'),
            name: 'SolidTimeUiLib',
            // the name of the output files when the build is run
            fileName: 'solidtime-ui-lib',
        },
        rollupOptions: {
            // make sure to externalize deps that shouldn't be bundled
            // into your library
            external: [
                'vue',
                'lucide-vue-next',
                '@floating-ui/vue',
                '@heroicons/vue',
                /^@heroicons\/vue\/.*/,
                '@vueuse/core',
                '@vueuse/integrations',
                /^@vueuse\/integrations\/.*/,
                'focus-trap',
                'chroma-js',
                'class-variance-authority',
                'clsx',
                'dayjs',
                /^dayjs\/.*/,
                'parse-duration',
                '@internationalized/date',
                'radix-vue',
                'reka-ui',
                'tailwind-merge',
            ],
            output: {
                // Provide global variables to use in the UMD build
                // for externalized deps
                globals: {
                    vue: 'Vue',
                },
            },
        },
    },
    resolve: {
        alias: {
            '@': resolve('../../'),
        },
    },
});
