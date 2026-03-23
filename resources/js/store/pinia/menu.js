import { defineStore } from 'pinia'
import sidebarItems from '@/routes/sidebar-items'

export const useMenuStore = defineStore('menu', {
    state: () => ({
        menuItems: [],
    }),
    getters: {
        items: state => [...state.menuItems, ...sidebarItems()],
    },
    actions: {
        setSidebarItems(data) {
            const userRoles = window.fabriqCms?.userRoles ?? []

            this.menuItems = data
                .filter(item => item.enabled)
                .filter(item => {
                    if (item.roles.includes('*')) {
                        return true
                    }

                    const matchedRoles = userRoles.filter(role => item.roles.includes(role))
                    return matchedRoles.length > 0
                })
        },
    },
})
