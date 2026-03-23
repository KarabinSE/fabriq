import { createPinia } from 'pinia'
import { useConfigStore } from './config'
import { useEchoStore } from './echo'
import { useMenuStore } from './menu'
import { usePageStore } from './page'
import { useRouteHistoryStore } from './routeHistory'
import { useUiStore } from './ui'
import { useUserStore } from './user'

const pinia = createPinia()

export function initializeLegacyStores() {
    return {
        config: useConfigStore(pinia),
        echo: useEchoStore(pinia),
        menu: useMenuStore(pinia),
        page: usePageStore(pinia),
        routeHistory: useRouteHistoryStore(pinia),
        ui: useUiStore(pinia),
        user: useUserStore(pinia),
    }
}

export {
    pinia,
    useConfigStore,
    useEchoStore,
    useMenuStore,
    usePageStore,
    useRouteHistoryStore,
    useUiStore,
    useUserStore,
}

export default pinia
