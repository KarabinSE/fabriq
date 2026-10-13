import axios from 'axios'
import { route } from "@/generated/helpers/route"

export default {
    async index (payload) {
        const { data } = await axios.get(route('menus.index'), payload)

        return data
    },

    async store (payload) {
        const { data } = await axios.post(route('menus.store'), payload)

        return data
    },

    async show (id, payload) {
        const { data } = await axios.get(route('menus.show', { id }), payload)

        return data
    },

    async showTree (id, payload) {
        const { data } = await axios.get(route('menus.items.tree.index', { id }), payload)

        return data
    },

    async updateTree (id, payload) {
        const { data } = await axios.patch(route('menus.items.tree.update', { id }), payload)

        return data
    }
}
