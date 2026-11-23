import axiosSetup from '@fabriq/config/api.js'

import 'vue-final-modal/style.css'
import '@fabriq/../css/fabriq.css'
import 'v-calendar/style.css'

import VCalendar from 'v-calendar'
import BlockTypes from '@fabriq/block-types/index.js'
import commonComponents from '@fabriq/components/common-components.js'
import clickOutside from '@fabriq/directives/click-outside'
import icons from '@fabriq/icons/index.js'

import floatingVue from '@fabriq/plugins/floating-vue'
import { vMaska } from 'maska/vue'
import FontAwesomePlugin from '@fabriq/plugins/fontawesome'

import eventBus from '@fabriq/services/eventBus'
import createFabriqRouter from '@fabriq/routes/router'
import { createPinia } from 'pinia'
import { createVfm } from 'vue-final-modal'
import Notifications from 'notiwind'
import { autoAnimatePlugin } from '@formkit/auto-animate/vue'
import { hasInjectionContext, inject, provide } from 'vue'
import { reactive } from 'vue'

const defaultColorsSchema = {
    button: [
        {
            label: 'Primär',
            color: 'bg-btn-primary border-btn-primary',
            value: 'primary',
        },
        {
            label: 'Primär kontur',
            color: 'border-btn-primary',
            value: 'primary-outline',
        },
        {
            label: 'Sekundär',
            color: 'bg-btn-secondary border-btn-secondary',
            value: 'secondary',
        },
        {
            label: 'Sekundär kontur',
            color: 'border-btn-secondary',
            value: 'secondary-outline',
        },
        {
            label: 'Tertiär',
            color: 'bg-btn-tertiary border-btn-tertiary',
            value: 'tertiary',
        },
        {
            label: 'Tertiär kontur',
            color: 'border-btn-tertiary',
            value: 'tertiary-outline',
        },
    ],
    background: [
        {
            label: 'Primär',
            color: 'bg-primary',
            value: 'primary',
        },
        {
            label: 'Sekundär',
            color: 'bg-secondary',
            value: 'secondary',
        },
        {
            label: 'Tertiär',
            color: 'bg-tertiary',
            value: 'tertiary',
        },
    ],
}

const fabriqPluginSymbol = 'fabriq-plugin'

const fabriqPlugin = {
    install(app, {
        customRoutes = [],
        colors = {
        },
    }) {
        const pinia = createPinia()
        const router = createFabriqRouter(app, customRoutes)
        const vfm = createVfm()

        axiosSetup(app)

        app.use(pinia)
            .use(router)
            .use(vfm)
            .use(FontAwesomePlugin)
            .use(BlockTypes)
            .use(commonComponents)
            .use(icons)
            .use(eventBus)
            .use(Notifications)
            .use(floatingVue)
            .use(VCalendar)
            .use(autoAnimatePlugin)
            .directive('maska', vMaska)
            .directive('click-outside', clickOutside)

        const fabriqSettings = reactive({
            colors: {
                ...Object.assign(defaultColorsSchema, colors),
            },
        })

        app.provide(fabriqPluginSymbol, fabriqSettings)
    },
}

function useFabriq() {
    if(!hasInjectionContext()) {
        throw new Error('Must be called within setup')

    }

    return inject(fabriqPluginSymbol, {
    })
}

export { fabriqPlugin, useFabriq }


