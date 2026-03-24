import { defineStore } from 'pinia'
import { useUserStore } from './user'

export const useEchoStore = defineStore('echo', {
    state: () => ({
        usersIdle: {},
    }),
    getters: {
        sortedUsersIdle: state => {
            const localCopy = { ...state.usersIdle }
            Object.keys(localCopy).forEach(key => {
                localCopy[key].sort((a, b) => a.timestamp - b.timestamp)
            })
            return localCopy
        },
        currentUserIsFirstIn() {
            const users = Object.values(this.sortedUsersIdle)[0]
            if (!users || users.length <= 1) {
                return true
            }

            const userStore = useUserStore()
            return users[0].id === userStore.user.id
        },
    },
    actions: {
        userJoining(data) {
            if (!this.usersIdle[data.identifier]) {
                return
            }

            const exists = this.usersIdle[data.identifier].findIndex(item => item.id === data.user.id) > -1
            if (!exists) {
                this.usersIdle[data.identifier].push(data.user)
            }
        },
        userLeaving(data) {
            if (!this.usersIdle[data.identifier]) {
                return
            }

            const index = this.usersIdle[data.identifier].findIndex(item => item.id === data.user.id)
            if (index > -1) {
                this.usersIdle[data.identifier].splice(index, 1)
            }
        },
        setUsersHere(data) {
            this.usersIdle = data
        },
    },
})
