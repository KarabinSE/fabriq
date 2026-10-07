import { createFabriqApp } from '@fabriq/fabriq'

import customRoutes from '@/routes/routes'

import '@/../css/app.css'
import blockTypes from '@fabriq/plugins/register-blocks'

createFabriqApp()
    .withRoutes(customRoutes)
    .use(blockTypes)
    .mount()
