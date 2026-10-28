import axios from 'axios'
import { route } from "@/generated/helpers/route"
import { useConfigStore } from '@fabriq/stores'

export class Block {
    isBlock = true
    locale  = 'sv'
    hidden  = false
    blockType = {}

    name  = ''
    content =  {
        id: '',
        name: '',
    }
    children = []

    constructor(data = {}, content = {}) {
        const config = useConfigStore()

        this.locale = config.activeLocale

        Object.entries(data).forEach(([k,v]) => this[k] = v)
        this.content = {
            ...structuredClone(content),
            id: 'i' + Math.random().toString(20).substring(2, 6),
        }

        this.children = data.children?.map(item => new Block(item, item.content)) ?? []
    }

    addChild(name, content){
        const child = new Block({
            name,
        }, content)

        this.children.push(child)
    }

    removeChild(index) {
        this.children.splice(index, 1)
    }
}

export default {
    async show (pageId, payload) {
        const { data } = await axios.get(route('pages.blocks.index', { id: pageId }), payload)

        return data
    },
    async put (pageId, blocks) {
        const { data } = await axios.put(route('blocks.update', { id: pageId }), { blocks: blocks })

        return data
    }
}
