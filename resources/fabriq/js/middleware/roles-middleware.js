export default function RolesMiddleware ({ app, to }) {
    const userRoles = window.fabriqCms.userRoles

    if (to.meta.roles.includes('*')) {
        return
    }

    const matchedRoles = userRoles.filter(element => to.meta.roles.includes(element))

    if (matchedRoles.length <= 0) {
        app.config.globalProperties.$toast.warning({
            title: 'Stopp i lagens namn!',
            message: 'Du saknar behörighet att visa sidan',
        })

        return false
    }

}
