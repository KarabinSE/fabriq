import axios from 'axios'
import { route } from "@/generated/helpers/route"

export default {
    async index (payload) {
        const { data } = await axios.get(route('contacts.index'), payload)

        return data
    },

    async store (payload) {
        const { data } = await axios.post(route('contacts.index'), payload)

        return data
    },

    async show (id, payload) {
        const { data } = await axios.get(route('contacts.show', { contact: id }), payload)

        return data
    },

    async update (id, payload) {
        const { data } = await axios.patch(route('contacts.update', { contact: id }), payload)

        return data
    },

    async destroy (id, payload) {
        const { data } = await axios.delete(route('contacts.destroy', {  contact: id }), payload)

        return data
    }
}
