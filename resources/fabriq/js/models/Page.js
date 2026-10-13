import axios from 'axios'
import { route } from "@/generated/helpers/route"

export default {
    async index (payload) {
        const { data } = await axios.get(route('pages.index'), payload)

        return data
    },

    async count (payload) {
        const { data } = await axios.get(route('model.count.show', { model: 'pages' }), payload)

        return data
    },

    async store (payload) {
        const { data } = await axios.post(route('pages.store'), payload)

        return data
    },

    async show (id, payload) {
        const { data } = await axios.get(route('pages.show', { id }), payload)

        return data
    },

    async update (id, object) {
        const { data } = await axios.patch(route('pages.update', { id }), object)

        return data
    },

    async destroy (id) {
        const { data } = await axios.delete(route('pages.destroy', { id }))

        return data
    },

    async publish (id) {
        const { data } = await axios.post(route('pages.publish.store', { id }))

        return data
    },

    async signedPreview (id) {
        const { data } = await axios.get(route('pages.signed-url.show', { id }))

        return data
    },

    async paths (id, payload) {
        const { data } = await axios.get(route('pages.paths.index', { id }), payload)

        return data
    },

    async clone (id, payload) {
        const { data } = await axios.post(route('pages.clone.store', { id }), payload)

        return data
    }
}
