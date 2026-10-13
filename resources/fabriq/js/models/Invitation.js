import axios from 'axios'
import { route } from "@/generated/helpers/route"

export default {
    endpoint: '/api/admin/invitations/',

    async show(id, payload) {
        const { data } = await axios.get(this.endpoint + id, payload)

        return data
    },

    async store (id, payload) {
        const { data } = await axios.post(route('invitations.store', { userId: id }), payload)

        return data
    },

    async destroy (id, payload) {
        const { data } = await axios.delete(route('invitations.destroy', { userId: id }), payload)

        return data
    }
}
