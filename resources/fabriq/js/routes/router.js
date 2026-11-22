import routes from '@fabriq/routes/fabriq-routes'
import { useUiStore, useRouteHistoryStore } from '@fabriq/stores'
import { createWebHistory, createRouter } from 'vue-router'

function createFabriqRouter (app, customRoutes = []) {
    const router = createRouter({
        history: createWebHistory(),
        routes: [...customRoutes, ...routes],
    })

    router.beforeEach(beforeEach)
    router.afterEach(afterEach)

    function afterEach (to, from) {
        const uiStore = useUiStore()

        uiStore.closeMenu()

        const Echo = app.config.globalProperties.$echo

        if (Echo) {

            const id = from.params.id
            const roomName = from.name
            const identifier = roomName + '.' + id
            const wsPrefix = window.fabriqCms.pusher.ws_prefix

            Echo.leave(wsPrefix + '.presence.' + identifier)

            if (from.meta.broadcastName) {
                const broadcastName = from.meta.broadcastName
                const capitalizedBroadcastName = broadcastName[0].toUpperCase() + broadcastName.slice(1)

                Echo.channel(`${wsPrefix}-${broadcastName}.${id}`)
                    .stopListening(`.${capitalizedBroadcastName}Updated`)

                Echo.channel(`${wsPrefix}-${broadcastName}.`)
                    .stopListening(`.${capitalizedBroadcastName}Updated`)
                    .stopListening(`.${capitalizedBroadcastName}Deleted`)
                    .stopListening(`.${capitalizedBroadcastName}Created`)
            }
        }
    }

    function beforeEach (to, from) {
        const routeHistoryStore = useRouteHistoryStore()

        routeHistoryStore.setFromRoute(from.name)

        if (!to.meta.middleware) return

        const middleware = Array.isArray(to.meta.middleware) ? to.meta.middleware : [to.meta.middleware]
        const context = {
            app,
            from,
            router,
            to, 
        }

        return runMiddleware(middleware, context)
    }

    async function runMiddleware (middleware, context, index = 0) {
        const currentMiddleware = middleware[index]

        if (!currentMiddleware) return

        const result = await currentMiddleware(context)

        if (result !== undefined) return result

        return runMiddleware(middleware, context, index + 1)
    }

    return router
}

export default createFabriqRouter
