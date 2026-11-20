import axiosSetup from '@fabriq/config/api.js'

import 'vue-final-modal/style.css'
import '@fabriq/../css/fabriq.css'
import 'v-calendar/style.css';

import VCalendar from 'v-calendar';
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

const fabriqPlugin = {
    install(app, {customRoutes = []}) {
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
    }
}

export {
    fabriqPlugin,
}
