import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useMenuStore } from './menu';
import Config from '@/models/Config';

export const useConfigStore = defineStore('config', () => {
    const config = ref({
        modules: [],
        supported_locales: {
            sv: {
                name: 'Swedish',
                script: 'Latn',
                native: 'Svenska',
                regional: 'sv_SE'
            }
        }
    });

    const activeLocale = ref('sv');

    const developmentMode = ref(false);

    const supportedLocales = computed(() => config.value.supported_locales);

    async function index() {
        const { data } = await Config.index();

        config.value = data;

        const menuStore = useMenuStore();

        menuStore.setSidebarItems(data.modules);
    }

    function setActiveLocale (locale) {
        activeLocale.value = locale;
    }

    function setDevelopmentMode (value) {
        developmentMode.value = value;
    }

    return {
        config,
        activeLocale,
        devMode: developmentMode,
        supportedLocales,
        index,
        setActiveLocale,
        setDevMode: setDevelopmentMode }
});
