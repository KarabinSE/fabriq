import axios from 'axios'
import { route } from "@/generated/helpers/route"

export default {
    async index (payload) {
        const { data } = await axios.get(route('tags.index'), payload)

        return data
    },

    async store (payload) {
        const { data } = await axios.post(route('tags.store'), payload)

        return data
    }
}
