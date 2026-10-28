import { defineStore } from "pinia";
import Vue, { computed, ref, toRaw } from "vue";
import { useConfigStore } from "./config";
import Page from "@fabriq/models/Page";
import axios from 'axios'
import { route } from '@/generated/helpers/route'
import { useUiStore } from '@fabriq/stores/ui'
import Blocks, { Block } from '@fabriq/models/Block'

class PageFetchError extends Error {
    constructor(message, error){
        super(message)
        this.message = message
        this.name = 'PageFetchError'
        this.error = error
    }
}

class PagePublishError extends Error {
    constructor(message, payload){
        super(message)
        this.message = message
        this.name = 'PagePublishError'
        this.payload = payload
    }
}
class PageUpdateError extends Error {
    constructor(message, payload){
        super(message)
        this.message = message
        this.name = 'PageUpdateError'
        this.payload = payload
    }
}

const defaultPageData = {
    id: 0,
    updated_at: '2020-01-01 10:00:00',
    localizedContent: {
        sv: {},
    },
    template: {
        data: {},
    },
}

export const usePageStore = defineStore('page', () => {
    const config = useConfigStore()
    const ui = useUiStore()

    const page = ref(structuredClone(defaultPageData));

    const localizedContent = ref({
        sv: {}
    })
    const wasInitiallyPublished = ref(true)

    const blocks = ref([])
    const groupedFields = computed(() => page.value.template.data?.groupedFields?.data ?? {})
    const activeLocaleBlocks = computed(() => blocks.value.filter(block => block.locale === config.activeLocale))
    const activeLocaleContent = computed(() => localizedContent.value[config.activeLocale])

    const filteredGroupedFields = computed( () => {
        if (!groupedFields.value || typeof groupedFields.value !== 'object') {
            return {}
        }

        return Object.fromEntries(
            Object.entries(groupedFields.value).filter(([groupKey]) => groupKey !== 'main_content')
        )
    })

    function setPage (data) {
        page.value = data;
        wasInitiallyPublished.value = data.published
    }

    function resetPage () {
        page.value = structuredClone(defaultPageData);
    }

    /**
     * fetches page content for each locale
     */
    async function fetchPage (pageId) {
        await config.initiated
        blocks.value = []

        fetchPageBlocks(pageId)

        const promises = Object.keys(config.supportedLocales)
            .map(async (locale) => await fetchPageForLocale(pageId, locale))

        const page = await Promise.all(promises)
        setPage(page[0])
    }

    async function fetchPageForLocale(pageId, locale) {
        const payload = {
            params: {
                include: 'content,template.groupedFields',
                append: 'paths',
                locale_override: locale,
            },
        }
        try {
            const { data } = await Page.show(pageId, payload)

            delete data.content.data.boxes
            Vue.set(localizedContent.value, locale, data.content.data)
            return data
        } catch (error) {
            throw new PageFetchError(`Failed to fetch page for locale: '${locale}`, error)
        }
    }

    async function fetchPageBlocks (pageId) {
        const payload = {
            params: {
                include: 'blockType,children',
                // 'filter[locale]': locale,
            }
        }
        try {
            const data  = await Blocks.show(pageId, payload)

            blocks.value = data.map(block => new Block(block, block.content))
        } catch (error) {
            console.error(error)
        }
    }

    async function update (pageId) {
        const payload = {
            name: page.value.name,
            published: page.value.published,
            localizedContent: localizedContent.value,
        }

        try {
            await Page.update(pageId, payload)
            await Blocks.put(pageId, blocks.value)

            wasInitiallyPublished.value = page.value.published
        } catch (error) {
            throw new PageUpdateError(error, { page: pageId, payload });
        }
    }

    async function publish (pageId) {
        try {
            await Page.publish(pageId)
        } catch (error) {
            throw new PagePublishError(error, { page: pageId })
        }
    }

    function addBlock(blockType) {
        const newBlock = new Block({
            name: blockType.name,
            newlyAdded: true,
            block_type_id: blockType.id,
            blockType: blockType,
        }, {
            name: blockType.name,
        })
        blocks.value.push(newBlock)

        ui.toggleOpenCard(newBlock.content.id)
    }

    function cloneBlock(blockToClone) {
        const clonedBlock = structuredClone(blockToClone)
        clonedBlock.name = 'Kopia av ' + clonedBlock.name

        const newBlock = new Block(clonedBlock, clonedBlock.content)

        blocks.value.push(newBlock)
    }

    function removeBlock(blockToRemove) {
        const index = blocks.value.findIndex(block => blockToRemove.content.id === block.content.id)

        blocks.value.splice(index, 1)
    }

    return {
        page,
        setPage,
        activeLocaleContent,
        fetchPage,
        localizedContent,
        filteredGroupedFields,
        wasInitiallyPublished,
        blocks: {
            index: activeLocaleBlocks,
            add: addBlock,
            clone: cloneBlock,
            remove: removeBlock,
        },
        update,
        publish,
        resetPage,
    }
});
