import { defineStore } from "pinia";
import Vue, { computed, ref, toRaw } from "vue";
import { useConfigStore } from "./config";
import Page from "@fabriq/models/Page";
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

    const wasInitiallyPublished = ref(true)
    const blocksLoading = ref(false)
    const page = ref(structuredClone(defaultPageData));

    const blocks = ref([])
    const localizedContent = ref({ sv: {} })

    const groupedFields = computed(() => page.value.template.data?.groupedFields?.data ?? {})

    const activeLocaleBlocks = computed(() => blocks.value.filter(block => block.locale === config.activeLocale))
    const activeLocaleContent = computed({
        get: () => localizedContent.value[config.activeLocale],
        set: (newContent) => localizedContent.value[config.activeLocale] = newContent
    })

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

    let markPageLoaded
    const pageLoaded = ref(new Promise((r) => markPageLoaded = r))
    /**
     * fetches page content for each locale
     */
    async function fetchPage (pageId) {
        pageLoaded.value = new Promise((r) => markPageLoaded = r)
        blocks.value = []
        await config.initiated

        const promises = Object.keys(config.supportedLocales)
            .map(async (locale) => await fetchPageForLocale(pageId, locale))

        const pages = await Promise.all([
            ...promises,
            fetchPageBlocks(pageId)
        ])
        setPage(pages[0])

        markPageLoaded()
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
            blocksLoading.value = true
            const data  = await Blocks.index(pageId, payload)

            blocks.value = data.map(block => new Block(block, block.content))
        } catch (error) {
            console.error(error)
        } finally {
            blocksLoading.value = false
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
            block_type: blockType,
        }, {
            name: blockType.name,
        })
        blocks.value.push(newBlock)

        ui.toggleOpenCard(newBlock.content.id)
    }

    function cloneBlock(blockToClone) {
        blocks.value.push(blockToClone.clone())
    }

    function removeBlock(block) {
        const index = blocks.value.findIndex(b => b.content.id === block.content.id)
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
            flat: blocks,
            index: activeLocaleBlocks,
            add: addBlock,
            clone: cloneBlock,
            remove: removeBlock,
        },
        update,
        publish,
        resetPage,
        blocksLoading,
        pageLoaded,
    }
});
