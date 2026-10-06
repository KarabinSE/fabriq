import Vue from 'vue'
import { pinia } from '@fabriq/plugins/pinia'
import axiosSetup from '@fabriq/config/api.js'

import createRouter from '@fabriq/routes/router.js'

import '@fabriq/../css/fabriq.css'
import App from '@fabriq/App.vue'
import BlockTypes from '@fabriq/block-types/index.js'
import commonComponents from '@fabriq/components/common-components.js'
import '@fabriq/directives/index.js'
import '@fabriq/filters/index.js'
import icons from '@fabriq/icons/index.js'
import '@fabriq/plugins/index.js'

import eventBus from '@fabriq/services/eventBus'

function createFabriqApp () {
    Vue.prototype.$eventBus = eventBus

    Vue.use(BlockTypes)
    Vue.use(commonComponents)
    Vue.use(icons)
    Vue.use(pinia)

    let customRoutes = []

    return {
        ...Vue,
        withRoutes(routes = []){
            customRoutes = routes

            return this
        },
        /** create a router with custom routes and mount the app */
        mount(target = '#app'){
            const app = new Vue({
                router: createRouter(customRoutes),
                render: (h) => h(App),
            })

            axiosSetup(app)

            app.$mount(target)

            return app
        },
    }
}

export {
    createFabriqApp,
}
