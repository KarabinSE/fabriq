import { defineStore } from 'pinia'

export const useUiStore = defineStore('ui', {
    state: () => ({
        menuOpen: false,
        openCards: [],
    }),
    actions: {
        toggleMenu() {
            this.menuOpen = !this.menuOpen
        },
        openMenu() {
            this.menuOpen = true
        },
        closeMenu() {
            this.menuOpen = false
        },
        toggleOpenCard(identifier) {
            const index = this.openCards.indexOf(identifier)
            if (index === -1) {
                this.openCards.push(identifier)
                return
            }

            this.openCards.splice(index, 1)
        },
    },
})
