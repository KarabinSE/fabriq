import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'

const laravelPlugin = (laravel as any).default || laravel
const forceManifestPath = {
    name: 'fabriq-force-manifest-path',
    enforce: 'post' as const,
    config() {
        return {
            build: {
                manifest: 'manifest.json',
            },
        }
    },
}

export default defineConfig({
    plugins: [
        laravelPlugin({
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
        forceManifestPath,
    ],
})
