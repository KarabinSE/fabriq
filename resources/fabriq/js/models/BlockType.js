import axios from 'axios'
import { route } from "@/generated/helpers/route"

export default {
    async index (payload) {
        const { data } = await axios.get(route('block-types.index'), payload)

        return data
    },

    async update (id, payload, config = {}) {
        const { data } = await axios.patch(route('block-types.update', { block_type: id }), payload, config)

        return data
    },

    async store (payload) {
        const { data } = await axios.post(route('block-types.store'), payload)

        return data
    },

    async destroy (id, payload) {
        const { data } = await axios.delete(route('block-types.destroy', { block_type: id }), payload)

        return data
    }
}
