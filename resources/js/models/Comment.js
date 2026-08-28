import axios from 'axios'
import { route } from "@/generated/helpers/route"

export default {
    endpoint: '/api/admin/',

    async index (modelName, modelId, payload) {
        const { data } = await axios.get(route('comments.commentable.index', { id: modelId, model: modelName }), payload)

        return data
    },

    async store (modelName, modelId, payload) {
        const { data } = await axios.post(route('comments.commentable.store', { id: modelId, model: modelName }), payload)

        return data
    },

    async show (id, payload) {
        const { data } = await axios.get(this.endpoint + id, payload)

        return data
    },

    async update (id, payload) {
        const { data } = await axios.patch(route('comments.update', { id }), payload)

        return data
    },

    async destroy (id, payload) {
        const { data } = await axios.delete(route('comments.destroy', { id }), payload)

        return data
    }
}
