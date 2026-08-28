import { defineStore } from "pinia";
import { computed, ref, toRaw } from "vue";
import { useConfigStore } from "./config";
import Page from "@/models/Page";

const queryParams = {
    include: 'template,template.groupedFields,slugs,localizedContent',
    locale: 'all',
    append: 'paths',
}

class PageFetchError extends Error {
    constructor(message){
        super(message)
        this.message = message
        this.name = 'PageFetchError'
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

    const page = ref(structuredClone(defaultPageData));

    const localizedContent = ref(null)
    const wasInitiallyPublished = ref(true)
    
    const fields = computed(() => page.value.template.data?.fields ?? {})
    const groupedFields = computed(() => page.value.template.data?.groupedFields?.data ?? {})

    const activeLocaleContent = computed(() => {
        if(!localizedContent.value?.[config.activeLocale]) {
            console.warn({
                message: `no localizedContent for locale ${config.activeLocale}`,
                page: page.value
            });
            
            return {}
        }

        return localizedContent.value[config.activeLocale]
    })

    function setPage (data) {
        page.value = data;
    }

    function resetPage () {
        page.value = structuredClone(defaultPageData);
    }

    const filteredGroupedFields = computed( () => {
        if (!groupedFields.value || typeof groupedFields.value !== 'object') {
            return {}
        }

        return Object.fromEntries(
            Object.entries(groupedFields.value).filter(([groupKey]) => groupKey !== 'main_content')
        )
    })
    
    async function fetchPage (pageId) {
        const payload = {
            params: queryParams,
        }

        try {
            page.value.id = 0
            const { data } = await Page.show(pageId, payload)

            page.value = data
            wasInitiallyPublished.value = data.published

            let temp = {}
            Object.entries(data.localizedContent.data).forEach(([locale, value]) => {
                temp[locale] = value.content.boxes ? value.content: {...value.content, boxes: []}
            })
            localizedContent.value = temp
        } catch (error) {
            throw new PageFetchError(error)
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

    function addBlock(block) {        
        if(activeLocaleContent.value.boxes.length > 0) {
            activeLocaleContent.value.boxes.at(-1).newlyAdded = false
        }

        activeLocaleContent.value.boxes.push({
            ...block,
            id: 'i' + Math.random().toString(20).substring(2, 6),
            newlyAdded: true,
        })
    }
    
    function removeBlock(index) {
        activeLocaleContent.value.boxes.splice(index, 1)
    }

    const blockIndex = computed({
        get() {
            return activeLocaleContent.value?.boxes ?? []
        },
        set(val) {
            activeLocaleContent.value.boxes = val
        }
    })

    return {
        page,
        setPage,
        activeLocaleContent,
        fetchPage,
        localizedContent,
        filteredGroupedFields,
        wasInitiallyPublished,
        blocks: {
            index: blockIndex,
            add: addBlock,
            remove: removeBlock,
        },
        update,
        publish,
        resetPage,
    }
});