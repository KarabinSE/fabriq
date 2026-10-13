import axios from 'axios'
import { route } from "@/generated/helpers/route"

export default {
    async show (id, payload) {
        const { data } = await axios.get(route('images.show', { id }), payload)

        return data
    },

    async index (payload) {
        const { data } = await axios.get(route('images.index'), payload)

        return data
    },

    async store (payload) {
        const { data } = await axios.post(route('uploads.images.store'), payload)

        return data
    },

    async count (payload) {
        const { data } = await axios.get(route('model.count.show', { model: 'images' }), payload)

        return data
    },

    async update (id, payload) {
        const { data } = await axios.patch(route('images.update', { id }), payload)

        return data
    },

    async destroy (id) {
        const { data } = await axios.delete(route('images.destroy', { id }))

        return data
    },

    async attachToModel (id, model, payload) {
        const { data } = await axios.post(route('images.imageable.store', { id, model }), payload)

        return data
    },

    async relatedIndex (id, model, payload = {}) {
        const { data } = await axios.get(route('images.imageable.index', { id, model }), payload)

        return data
    },

    async srcSet (id, payload = {}) {
        const { data } = await axios.get(route('images.src-set.show', { id }), payload)

        return data
    }
}
