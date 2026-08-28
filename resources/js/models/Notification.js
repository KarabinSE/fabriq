import axios from 'axios'
import { route } from "@/generated/helpers/route"

export default {
    async index (payload) {
        const { data } = await axios.get(route('user.notifications.index'), payload)

        return data
    },

    async update (id, payload) {
        const { data } = await axios.patch(route('user.notifications.update', { id }), payload)

        return data
    }
}
