import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import AuthenticatedUser from '@/models/AuthenticatedUser.js'
import Notification from '@/models/Notification.js'

export const useUserStore = defineStore('user', () => { 
    const user = ref({
        id: 0,
        email: '',
        created_at: '',
        updated_at: '',
        role_list: [],
        image: {
            data: {}
        },
        timezone: 'Europe/Stockholm'
    });

    const notifications = ref([]);

    const users = ref([]);

    const roles = computed(() => user.value.role_list);

    const isDev = computed(() => user.value.role_list.includes('dev'));

    const timezone = computed(() => user.value.timezone);

    async function index () {
        const { data } = await AuthenticatedUser.index();
        
        user.value = data;
    }

    async function fetchNotifications () {
        try {
            const payload = {
                params: {
                    'filter[unseen]': true,
                    number: 300,
                    field: 'id'
                }
            }

            const { data } = await Notification.index(payload);

            notifications.value = data;
        } catch (error) {
            console.log(error);
        }
    }

    function setUser (data) {
        user.value = data;
    }

    function setUsers ( data ) { 
        users.value = data;
    }

    return {
        user,
        notifications,
        users,
        roles,
        isDev,
        timezone,
        index,
        fetchNotifications,
        setUser,
        setUsers
    }
})