import { useModal } from 'vue-final-modal'
import { clsx } from 'clsx'
import { twMerge } from 'tailwind-merge'

export function cn(...inputs) {
    return twMerge(clsx(inputs))
}

export function createModal(component, props = {}) {
    const modal = useModal({
        component,
        attrs: {
            ...props,
            onCloseModal: () => modal.close()
        },
    })

    modal.open()

    return modal
}