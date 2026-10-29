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

    const localizedContent = ref({ sv: {} })
    const localizedBlocks = ref({ sv: [] })

    const groupedFields = computed(() => page.value.template.data?.groupedFields?.data ?? {})
    const activeLocaleBlocks = computed({
        get: () => {
            if(!localizedBlocks.value[config.activeLocale]) {
                Vue.set(localizedBlocks.value, config.activeLocale, [])
            }
            return localizedBlocks.value[config.activeLocale]
        },
        set: (newBlocks) => localizedBlocks.value[config.activeLocale] = newBlocks
    })
    const flatBlocks = computed(() => Object.values(localizedBlocks.value).flat())

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

    /**
     * fetches page content for each locale
     */
    async function fetchPage (pageId) {
        localizedBlocks.value = { sv: [] }
        await config.initiated

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
            blocksLoading.value = true
            const data  = await Blocks.index(pageId, payload)

            data.forEach(block => {
                if(localizedBlocks.value[block.locale]) {
                    localizedBlocks.value[block.locale].push(new Block(block, block.content))
                } else {
                    Vue.set(localizedBlocks.value, block.locale, [new Block(block, block.content)])
                }
            })
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
            await Blocks.put(pageId, flatBlocks.value)

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
        activeLocaleBlocks.value.push(newBlock)

        ui.toggleOpenCard(newBlock.content.id)
    }

    function cloneBlock(blockToClone) {
        activeLocaleBlocks.value.push(blockToClone.clone())
    }

    function removeBlock(index) {
        console.log(index);

        activeLocaleBlocks.value.splice(index, 1)
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
            flat: flatBlocks,
            index: activeLocaleBlocks,
            add: addBlock,
            clone: cloneBlock,
            remove: removeBlock,
        },
        update,
        publish,
        resetPage,
        blocksLoading,
    }
});
