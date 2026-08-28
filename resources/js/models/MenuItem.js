import axios from 'axios'
import { route } from "@/generated/helpers/route"

export default {
    async show (id, object) {
        const { data } = await axios.get(route('menu-items.show', { id }), object)

        return data
    },

    async update (id, object) {
        const { data } = await axios.patch(route('menu-items.update', { id }), object)

        return data
    },

    async store (id, object) {
        const { data } = await axios.post(route('menus.items.store', { id }), object)

        return data
    },

    async destroy (id) {
        const { data } = await axios.delete(route('menu-items.destroy', { id }))

        return data
    }

}
