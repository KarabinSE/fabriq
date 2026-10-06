import { createFabriqApp } from '@fabriq/fabriq'

import customRoutes from '@/routes/routes'

import '@/../css/app.css'
import blockTypes from '@/block-types/index.js'

createFabriqApp()
    .withRoutes(customRoutes)
    .use(blockTypes)
    .mount()
