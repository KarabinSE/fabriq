import axios from 'axios'
import { route } from "@/generated/helpers/route"

export default {
    async index (payload) {
        const { data } = await axios.get(route('smart-blocks.index'), payload)

        return data
    },

    async store (payload) {
        const { data } = await axios.post(route('smart-blocks.store'), payload)

        return data
    },

    async update (id, payload) {
        const { data } = await axios.patch(route('smart-blocks.update', { smart_block: id }), payload)

        return data
    },

    async show (id, payload) {
        const { data } = await axios.get(route('smart-blocks.show', { smart_block: id }), payload)

        return data
    },

    async destroy (id) {
        const { data } = await axios.delete(route('smart-blocks.destroy', { smart_block: id }))

        return data
    }
}
