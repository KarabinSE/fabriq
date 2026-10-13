import axios from 'axios'
import { route } from "@/generated/helpers/route"

export default {
    async show (id, payload) {
        const { data } = await axios.get(route('videos.show', { id }), payload)

        return data
    },

    async index (payload) {
        const { data } = await axios.get(route('videos.index'), payload)

        return data
    },

    async update (id, payload) {
        const { data } = await axios.patch(route('videos.update', { id }), payload)

        return data
    },

    async destroy (id) {
        const { data } = await axios.delete(route('videos.destroy', { id }))

        return data
    }
}
