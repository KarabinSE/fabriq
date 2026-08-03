import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'
import { useMenuStore } from './menu';

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

    const devMode = ref(false);

    const supportedLocales = computed(() => config.value.supported_locales);

    async function index() {
        const { data } = await axios.get('/api/config');
 
        config.value = data.data;

        const menuStore = useMenuStore();

        menuStore.setSidebarItems(data.data.modules);
    }

    function setActiveLocale (locale) {
        activeLocale.value = locale;
    }

    function setDevMode (value) {
        devMode.value = value;
    }

    return { 
        config, 
        activeLocale, 
        devMode, 
        supportedLocales, 
        index, 
        setActiveLocale, 
        setDevMode }
});