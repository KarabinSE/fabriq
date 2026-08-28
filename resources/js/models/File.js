import axios from 'axios'
import { route } from "@/generated/helpers/route"

export default {
    endpoint: '/api/admin/files/',

    async show (id, payload) {
        const { data } = await axios.get(route('files.show', { id }), payload)

        return data
    },

    async index (payload) {
        const { data } = await axios.get(route('files.index'), payload)

        return data
    },

    async count (payload) {
        const { data } = await axios.get(route('model.count.show', { model: 'files' }), payload)

        return data
    },

    async update (id, payload) {
        const { data } = await axios.patch(route('files.update', { id }), payload)

        return data
    },

    async destroy (id) {
        const { data } = await axios.delete(route('files.destroy', { id }))

        return data
    },

    async attachToModel (id, model, payload) {
        const { data } = await axios.post(this.endpoint + id + '/' + model, payload)

        return data
    },

    async relatedIndex (id, model, payload = {}) {
        const { data } = await axios.get('/api/admin/' + model + '/' + id + '/images', payload)

        return data
    }
}
