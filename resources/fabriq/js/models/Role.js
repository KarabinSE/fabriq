import axios from 'axios'
import { route } from "@/generated/helpers/route"

export default {
    async index (payload) {
        const { data } = await axios.get(route('roles.index'), payload)

        return data
    }
}
