import vue from '@vitejs/plugin-vue2'
import laravel from 'laravel-vite-plugin'
import tailwindcss from '@tailwindcss/vite'
import { defineConfig, splitVendorChunkPlugin } from 'vite'

export default defineConfig({
    plugins: [
        laravel(
            ['/resources/css/fabriq.css', 'resources/js/fabriq.js'],
        ),

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
