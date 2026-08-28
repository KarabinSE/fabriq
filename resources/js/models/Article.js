import axios from 'axios'
import { route } from '@/generated/helpers/route'

export default {
    async index (payload) {
        const { data } = await axios.get(route('articles.index'), payload)

        return data
    },

    async store (payload) {
        const { data } = await axios.post(route('articles.store'), payload)

        return data
    },

    async count (payload) {
        const { data } = await axios.get(route('model.count.show', { model: 'articles' }), payload)

        return data
    },

    async show (id, payload) {
        const { data } = await axios.get(route('articles.show', { article: id }), payload)

        return data
    },

    async update (id, object) {
        const { data } = await axios.patch(route('articles.update', { article: id }), object)

        return data
    },

    async destroy (id) {
        const { data } = await axios.delete(route('articles.destroy', { article: id }))

        return data
    }
}
