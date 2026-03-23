import axios from 'axios'
import { defineStore } from 'pinia'
import { useMenuStore } from './menu'

export const useConfigStore = defineStore('config', {
    state: () => ({
        config: {
            modules: [],
            supported_locales: {
                sv: {
                    name: 'Swedish',
                    script: 'Latn',
                    native: 'Svenska',
                    regional: 'sv_SE',
                },
            },
        },
        activeLocale: 'sv',
        devMode: false,
    }),
    getters: {
        supportedLocales: state => state.config.supported_locales,
    },
    actions: {
        setConfig(data) {
            this.config = data
        },
        setActiveLocale(locale) {
            this.activeLocale = locale
        },
        setDevMode(enabled) {
            this.devMode = enabled
        },
        async index() {
            const { data } = await axios.get('/api/config')
            this.setConfig(data.data)

            const menuStore = useMenuStore()
            menuStore.setSidebarItems(data.data.modules)
        },
    },
})
