import axios from 'axios'
import { route } from "@/generated/helpers/route"

export default {
    async index (payload) {
        const { data } = await axios.get(route('user.index'), payload)

        return data
    },

    async update (object) {
        const { data } = await axios.patch(route('user.update'), object)

        return data
    },

    async sendVerificationRequest () {
        const { data } = await axios.post(route('user.send-email-verification.store'))

        return data
    },

    async deleteImage () {
        const { data } = await axios.delete(route('user.image.destroy'))

        return data
    }
}
