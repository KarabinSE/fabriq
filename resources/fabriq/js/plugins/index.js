// import '@fabriq/plugins/laravel-echo'
// import '@fabriq/plugins/toast'
// import '@fabriq/plugins/v-calendar'
// import '@fabriq/plugins/vue-final-modal'
// import '@fabriq/plugins/font-awesome'
// import '@fabriq/plugins/vue-slide-up-down'

import floatingVue from '@fabriq/plugins/floating-vue'
import { vMaska } from "maska/vue"

export default {
    install (app) {
        app.use(floatingVue)
        app.directive('maska', vMaska)
    }
}
