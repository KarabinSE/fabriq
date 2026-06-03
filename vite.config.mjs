import vue from '@vitejs/plugin-vue2'
import laravel from 'laravel-vite-plugin'
import { defineConfig } from 'vite'

export default defineConfig({
    plugins: [
        laravel([
            // 'resources/css/app.css',
            'resources/js/fabriq.js',
        ]),

        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (!id.includes('node_modules')) {
                        return
                    }

                    // Keep chunking simple: only isolate the heaviest vendor groups.
                    if (id.includes('/vue/') || id.includes('/vuex/') || id.includes('/vue-router/')) {
                        return 'vendor-vue'
                    }

                    if (id.includes('/@tiptap/') || id.includes('/prosemirror-')) {
                        return 'vendor-editor'
                    }

                    if (id.includes('/v-calendar/') || id.includes('/dropzone/') || id.includes('/font') || id.includes('/sortablejs')) {
                        return 'vendor-ui'
                    }

                    if (id.includes('/date-fns/') || id.includes('/axios/') || id.includes('/vee-validate')) {
                        return 'vendor-utilities'
                    }
                },
            },
        },
    },
})
