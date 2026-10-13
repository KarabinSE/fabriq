import axios from 'axios'
import { route } from "@/generated/helpers/route"

export default {
    async index (payload) {
        const { data } = await axios.get(route('users.index'), payload)

        return data
    },

    async show (id, payload) {
        const { data } = await axios.get(route('users.show', { user: id }), payload)

        return data
    },

    async update (id, object) {
        const { data } = await axios.patch(route('users.update', { user: id }), object)

        return data
    },

    async store (object) {
        const { data } = await axios.post(route('users.store'), object)

        return data
    },

    async destroy (id) {
        const { data } = await axios.delete(route('users.destroy', { user: id }))

        return data
    }

}
