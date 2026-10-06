import { defineStore } from "pinia";
import { ref } from "vue";

export const useRouteHistoryStore = defineStore('routeHistory', () => {
    const lastRoute = ref(null);

    function setFromRoute (data) {
        lastRoute.value = data;
    }

    return {
        lastRoute,
        setFromRoute
    }
});