import { defineStore } from 'pinia'
import AuthenticatedUser from '@/models/AuthenticatedUser'
import Notification from '@/models/Notification'

export const useUserStore = defineStore('user', {
    state: () => ({
        user: {
            id: 0,
            email: '',
            created_at: '',
            updated_at: '',
            role_list: [],
            image: {
                data: {},
            },
            timezone: 'Europe/Stockholm',
        },
        notifications: [],
        users: [],
    }),
    getters: {
        roles: state => state.user.role_list,
        isDev: state => state.user.role_list.includes('dev'),
        timezone: state => state.user.timezone,
    },
    actions: {
        setUser(data) {
            this.user = data
        },
        setUsers(data) {
            this.users = data
        },
        setNotifications(data) {
            this.notifications = data
        },
        async index() {
            const { data } = await AuthenticatedUser.index()
            this.setUser(data)
        },
        async fetchNotifications() {
            try {
                const payload = {
                    params: {
                        'filter[unseen]': true,
                        number: 300,
                        field: 'id',
                    },
                }
                const { data } = await Notification.index(payload)
                this.setNotifications(data)
            } catch (error) {
                console.error(error)
            }
        },
    },
})
