import SidebarItems from '@/routes/sidebar-items.js'
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useMenuStore = defineStore('menu', () => {

    const menuItems = ref([]);

    const fullMenuItems = computed(() => [...menuItems.value, ...SidebarItems()]);

    function setSidebarItems (data) {
        const userRoles = window.fabriqCms.userRoles;

        // Filter if enabled
        menuItems.value = data.filter(item => {
            return item.enabled;
        }).filter(item => {
            // Filter user roles
            if (item.roles.includes('*')) {
                return true;
            }

            const matchedRoles = userRoles.filter(element => item.roles.includes(element));

            return matchedRoles.length > 0;
        });
    }

    return {
        menuItems,
        fullMenuItems,
        setSidebarItems
    }
});
