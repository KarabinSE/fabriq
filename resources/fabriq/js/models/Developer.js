import axios from 'axios'
import { route } from "@fabriq/generated/helpers/route"

export default {
    async bustCache (payload) {
        const { data } = await axios.post(route('bust-cache.store'), payload)

        return data
    }
}
