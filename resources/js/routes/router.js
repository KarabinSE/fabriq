import { pinia } from '@/plugins/pinia'
import routes from '@/routes/fabriq-routes'
import userRoutes from '@/routes/routes'
import { useUiStore, useRouteHistoryStore } from '@/stores'
import Vue from 'vue'
import VueRouter from 'vue-router'

Vue.use(VueRouter)
const router = createRouter()

export default router

/**
 * Create a new router instance.
 *
 * @return {VueRouter}
 */
function createRouter () {
    const router = new VueRouter({
        mode: 'history',
        routes: [...userRoutes, ...routes]
    })

    router.beforeEach(beforeEach)
    router.afterEach(afterEach)

    return router
}

function afterEach (to, from) {
    const uiStore = useUiStore();

    uiStore.closeMenu();

    const Echo = router.app.$echo
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

function beforeEach (to, from, next) {
    const routeHistoryStore = useRouteHistoryStore();

    routeHistoryStore.setFromRoute(from.name);

    if (to.meta.middleware) {
        const middleware = Array.isArray(to.meta.middleware) ? to.meta.middleware : [to.meta.middleware]

        const context = {
            from,
            next,
            router,
            to,
        }
        const nextMiddleware = nextFactory(context, middleware, 1)

        return middleware[0]({ ...context, next: nextMiddleware })
    }

    next()
}

// Creates a `nextMiddleware()` function which not only
// runs the default `next()` callback but also triggers
// the subsequent Middleware function.
function nextFactory (context, middleware, index) {
    const subsequentMiddleware = middleware[index]
    // If no subsequent Middleware exists,
    // the default `next()` callback is returned.
    if (!subsequentMiddleware) return context.next

    return (...parameters) => {
        // Run the default Vue Router `next()` callback first.
        context.next(...parameters)
        // Then run the subsequent Middleware with a new
        // `nextMiddleware()` callback.
        const nextMiddleware = nextFactory(context, middleware, index + 1)
        subsequentMiddleware({ ...context, next: nextMiddleware })
    }
}
