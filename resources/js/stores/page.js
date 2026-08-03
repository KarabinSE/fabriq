import { defineStore } from "pinia";
import { ref } from "vue";

export const usePageStore = defineStore('page', () => {

    const page = ref({
        id: 0,
        updated_at: '2020-01-01 10:00:00',
        localizedContent: {
            sv: {},
        },

        template: {
            data: {},
        },
    });

    function setPage (data) {
        page.value = data;
    }

    return {
        page,
        setPage
    }
});