import Vue from 'vue'

import Api from '@fabriq/plugins/toast/api.js'
import Toast from '@fabriq/plugins/toast/Toast.vue'

const Plugin = (Vue, options = {}) => {
    const methods = Api(Vue, options)
    Vue.$toast = methods
    Vue.prototype.$toast = methods
}

Toast.install = Plugin

Vue.use(Toast, {
    // One of options
    dark: true,
    duration: 5000,
    position: 'bottom-right'
})
