import axios from 'axios'
import { route } from "@/generated/helpers/route"

export default {
    async index () {
        const { data } = await axios.get(route('config.index'))

        return data
    }
}
