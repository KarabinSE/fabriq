import { defineStore } from 'pinia'

export const useRouteHistoryStore = defineStore('routeHistory', {
    state: () => ({
        lastRoute: null,
    }),
    actions: {
        setFromRoute(routeName) {
            this.lastRoute = routeName
        },
    },
})
