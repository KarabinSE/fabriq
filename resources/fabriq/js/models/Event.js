import axios from 'axios'
import { route } from "@/generated/helpers/route"

export default {
    async index (payload) {
        const { data } = await axios.get(route('events.index'), payload)

        return data
    },

    async show (id, payload) {
        const { data } = await axios.get(route('events.show', { event: id }), payload)

        return data
    },

    async update (id, payload) {
        const { data } = await axios.patch(route('events.update', { event: id }), payload)

        return data
    },

    async store (payload) {
        const { data } = await axios.post(route('events.store'), payload)

        return data
    },

    async destroy (id) {
        const { data } = await axios.delete(route('events.destroy', { event: id }))

        return data
    }
}
