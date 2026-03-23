import { defineStore } from 'pinia'

export const usePageStore = defineStore('page', {
    state: () => ({
        page: {
            id: 0,
            updated_at: '2020-01-01 10:00:00',
            localizedContent: {
                sv: {},
            },
            template: {
                data: {},
            },
        },
    }),
    actions: {
        setPage(data) {
            this.page = data
        },
    },
})
