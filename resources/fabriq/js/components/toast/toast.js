import { notify } from 'notiwind'

export const toast = {
    open({ title, icon, text, data, action, type = 'success', group = 'toasts'}, timeout = 4000) {
        notify({
            title,
            text,
            group,
            type,
            action,
            icon,
            data,
        }, timeout)
    },
    success(options, timeout) {
        toast.open(Object.assign({}, options, {
            type: 'success',
            icon: options.icon ?? getIcon('success'),
        }), timeout)
    },
    info(options, timeout) {
        toast.open(Object.assign({}, options, {
            type: 'info',
            icon: options.icon ?? getIcon('info'),
        }), timeout)
    },
    error(options, timeout) {
        toast.open(Object.assign({}, options, {
            type: 'error',
            icon: options.icon ?? getIcon('error'),
        }), timeout)
    },
    warning(options, timeout) {
        toast.open(Object.assign({}, options, {
            type: 'warning',
            icon: options.icon ?? getIcon('warning'),
        }), timeout)
    },
    authorization(options, timeout) {
        toast.open(Object.assign({}, options, {
            type: 'warning',
            icon: options.icon ?? getIcon('warning'),
        }), timeout)
    },
    declined(options, timeout) {
        toast.open(Object.assign({}, options, {
            type: 'warning',
            icon: options.icon ?? getIcon('warning'),
        }), timeout)
    },
}

const typeIcons = {
    success: ['👍', '👐', '🙌', '👌', '✌', '💪', '✊', '👏', '✨'],
    info: ['🤖', '💁'],
    error: ['😢', '🤯', '😔', '😭'],
    warning: ['😳', '😬', '🥺'],
    authorization: ['🚔', '🚓'],
    declined: ['🙅‍♂️']
}

const getIcon = (type) => {
    const icons = typeIcons[type]

    return icons[Math.floor(Math.random() * icons.length)]
}
