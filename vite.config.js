import vue from '@vitejs/plugin-vue2'
import laravel from 'laravel-vite-plugin'
import tailwindcss from '@tailwindcss/vite'
import { defineConfig } from 'vite'
import path from 'path'

/** @type {import('vite').UserConfig} */
export default defineConfig({
    plugins: [
        laravel(['resources/css/app.css', 'resources/js/app.js']),

        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
            '@fabriq': path.resolve(__dirname, './resources/fabriq/js'),
        }
    },
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (!id.includes('node_modules')) {
                        return
                    }

                    // Keep chunking simple: only isolate the heaviest vendor groups.
                    if (id.includes('/vue/') || id.includes('/pinia/') || id.includes('/vue-router/')) {
                        return 'vendor-vue'
                    }

                    if (id.includes('/@tiptap/') || id.includes('/prosemirror-')) {
                        return 'vendor-editor'
                    }

                    if (id.includes('/dropzone/') || id.includes('/font') || id.includes('/sortablejs')) {
                        return 'vendor-ui'
                    }

                    if (id.includes('/date-fns/') || id.includes('/axios/') || id.includes('/vee-validate') || id.includes('/v-calendar/')) {
                        return 'vendor-utilities'
                    }
                },
            },
        },
    }
})
