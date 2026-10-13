import axios from 'axios'
import { route } from "@/generated/helpers/route"

export default {
    async index (payload) {
        const { data } = await axios.get(route('pages-tree.index'), payload)

        return data
    },

    async update (payload) {
        const { data } = await axios.patch(route('pages-tree.update'), payload)

        return data
    }
}
