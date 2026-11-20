export default function PresenceMiddleware ({ app, to, store }) {
    // Check if Echo is enabled
    if (!app.config.globalProperties.$echo) {
        return
    }
    const Echo = app.config.globalProperties.$echo
    const id = to.params.id
    const wsPrefix = window.fabriqCms.pusher.ws_prefix
    const roomName = to.name
    const identifier = roomName + '.' + id

    Echo.join(wsPrefix + '.presence.' + identifier)
        .here((users) => {
            const object = {}
            object[identifier] = users
            store.commit('echo/SET_USERS_HERE', object)
        })
        .joining((user) => {
            store.commit('echo/USER_JOINING', { identifier: identifier, user: user })
        })
        .leaving((user) => {
            store.commit('echo/USER_LEAVING', { identifier: identifier, user: user })
        })

}
