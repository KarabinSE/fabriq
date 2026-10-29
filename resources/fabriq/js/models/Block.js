import axios from 'axios'
import { route } from "@/generated/helpers/route"
import { useConfigStore } from '@fabriq/stores'

export class Block {
    isBlock = true
    locale  = 'sv'
    hidden  = false
    block_type = {}

    name  = ''
    content =  {}
    children = []

    constructor(data = {}) {
        if(data.isBlock) {
            return data
        }

        const config = useConfigStore()

        this.locale = config.activeLocale

        Object.entries(data).forEach(([k, v]) => this[k] = v)
        this.content = {
            ...structuredClone(data.content),
            id: 'i' + Math.random().toString(20).substring(2, 6),
        }

        // convert children to Block:s
        this.children = data.children?.map(item => new Block(item)) ?? []
    }

    addChild(block){
        this.children.push(block)
    }

    clone(){
        const clone = structuredClone(this)

        clone.name = 'Kopia av ' + clone.name
        clone.uuid = null
        clone.id = null
        delete clone.isBlock

        return new Block(clone)
    }

    removeChild(index) {
        this.children.splice(index, 1)
    }
}

export default {
    async index (pageId, payload) {
        const { data } = await axios.get(route('pages.blocks.index', { id: pageId }), payload)

        return data
    },
    async put (pageId, blocks) {
        const { data } = await axios.put(route('blocks.update', { id: pageId }), { blocks: blocks })

        return data
    }
}
