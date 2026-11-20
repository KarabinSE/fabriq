import { fabriqPlugin } from '@fabriq/fabriq'

import customRoutes from '@/routes/routes'

import '@/../css/app.css'
import blockTypes from '@fabriq/plugins/register-blocks'
import { createApp } from 'vue'
import App from '@fabriq/App.vue'

const app = createApp(App)
    .use(fabriqPlugin, { customRoutes })
    .use(blockTypes)
    .mount('#app')
