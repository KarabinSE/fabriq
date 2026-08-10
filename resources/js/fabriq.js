import Vue from 'vue'
import { pinia } from '@/plugins/pinia'
import axiosSetup from '@/config/api.js'

import router from '@/routes/router.js'

import '@/../css/fabriq.css'
import App from '@/App.vue'
import blockTypes from "@/block-types/index.js";
import commonComponents from "@/components/common-components.js";
import '@/directives/index.js'
import '@/filters/index.js'
import icons from "@/icons/index.js";
import '@/plugins/index.js'

import eventBus from "@/services/eventBus";

Vue.prototype.$eventBus = eventBus;

Vue.use(blockTypes);

Vue.use(commonComponents);

Vue.use(icons);

Vue.use(pinia);

const app = new Vue({
    router,
    render: (h) => h(App),
}).$mount("#app");

axiosSetup(app);
