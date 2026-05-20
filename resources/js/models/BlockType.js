import axios from 'axios'

export default {
    endpoint: '/api/admin/block-types/',

    async index (payload) {
        const { data } = await axios.get(this.endpoint, payload)

        return data
    },

    async update (id, payload, config = {}) {
        const { data } = await axios.patch(this.endpoint + id, payload, config)

        return data
    },

    async store (payload) {
        const { data } = await axios.post(this.endpoint, payload)

        return data
    },

    async destroy (id, payload) {
        const { data } = await axios.delete(this.endpoint + id, payload)

        return data
    }
}
