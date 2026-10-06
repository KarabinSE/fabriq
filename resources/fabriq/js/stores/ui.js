import { defineStore } from "pinia";
import { ref } from "vue";

export const useUiStore = defineStore('ui', () => {
    const menuOpen = ref(false);
    const openCards = ref([]);

    function toggleMenu () {
        menuOpen.value = !menuOpen.value;
    }

    function openMenu () {
        menuOpen.value = true;
    }

    function closeMenu () {
        menuOpen.value = false;
    }

    function toggleOpenCard (identifier) {
        const index = openCards.value.indexOf(identifier);

        if (index === -1) {
            openCards.value.push(identifier);
        } else {
            openCards.value.splice(index, 1);
        }
    }

    return {
        menuOpen,
        openCards,
        toggleMenu,
        openMenu,
        closeMenu,
        toggleOpenCard
    }
});