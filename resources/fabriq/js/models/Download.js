import axios from 'axios'
import { route } from "@/generated/helpers/route"

export default {
    async index (payload) {
        const localPayload = {
            responseType: 'blob',
            ...payload
        }

        return await axios.get(route('downloads.index'), localPayload)
    },

    async show (id, payload) {
        const localPayload = {
            responseType: 'blob',
            ...payload
        }
        const response = await axios.get(route('downloads.show', { id }), localPayload)

        return response
    },

    async handleBlobDownload (data, headers) {
        const anchor = document.createElement('a')
        document.body.appendChild(anchor)

        const binaryData = []
        binaryData.push(data)

        const objectUrl = window.URL.createObjectURL(new Blob(binaryData, { type: headers['Content-Type'] }))

        anchor.href = objectUrl
        anchor.download = headers['x-filename']
        anchor.click()

        window.URL.revokeObjectURL(objectUrl)
        anchor.remove()
    }
}
