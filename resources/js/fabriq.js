import Vue from 'vue'
import { pinia } from '@/plugins/pinia'
import axiosSetup from '@/config/api.js'

import router from '@/routes/router.js'

import '@/../css/fabriq.css'
import App from '@/App.vue'
import '@/block-types/index.js'
import '@/components/common-components.js'
import '@/directives/index.js'
import '@/filters/index.js'
import '@/icons/index.js'
import '@/plugins/index.js'

Vue.prototype.$eventBus = new Vue()

const app = new Vue({
    router,
    pinia,
    render: h => h(App)
}).$mount('#app')

axiosSetup(app)
