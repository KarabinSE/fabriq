import { defineStore } from "pinia";
import { computed, readonly, ref, watch } from "vue";
import { usePageStore } from "./page";
import axios from "axios";
import { route } from '@/generated/helpers/route'
import { useDebounceFn } from '@vueuse/core'

class MissingIFrameError extends Error {
    constructor(message) {
        super(message)
        this.name = 'MissingIFrameError'
        this.message = message ?? 'Missing iframe'
    }
}

export const usePreviewStore = defineStore('preview', () => {
    const pageStore = usePageStore()

    const iframeReference = ref(null)
    const previewUrl = ref(null)
    const previewVisible = ref(false)

    const previewPayload = computed(() => ({
        name: pageStore.page.name,
        published: pageStore.page.published,
        id: pageStore.page.id,
        content: pageStore.localizedContent,
        blocks: pageStore.blocks.flat,
    }))

    const page = computed(() => pageStore.page)

    const loaded = ref(false)

    const debouncedUpdatePreview = useDebounceFn(updatePreview, 500)
    watch(previewPayload, async () => {
        if(!loaded.value) {
            return
        }

        debouncedUpdatePreview()
    }, {
        deep: true
    })

    /**
     * whenever we enter a new page, update the preview url.
     * If there is no preview generated for the page, generate one
     * and update the url again.
     */
    watch(() => page.value.id, async () => {
        if(page.value.id) {
            await pageStore.pageLoaded
            await updatePreview()
            loaded.value = true
        }
    })

    async function updatePreview () {
        try {
            const { data } = await axios.put(route('previews.update', { id: pageStore.page.id }), previewPayload.value)

            previewUrl.value = data.preview_url

            postToIframe(iframeReference.value)
        } catch (error) {
            console.warn(error)
        }
    }

    function postToIframe() {
        if(!iframeReference.value) {
            throw new MissingIFrameError()
        }

        const targetOrigin = new URL(previewUrl.value).origin

        iframeReference.value.contentWindow.postMessage({ type: 'fabriq-preview-ready', url: previewUrl.value }, targetOrigin)
    }

    function locateBlock(blockId) {
        if(!iframeReference.value) {
            throw new MissingIFrameError()
        }

        const targetOrigin = new URL(previewUrl.value).origin
        iframeReference.value.contentWindow.postMessage({ type: 'fabriq-locate-block', url: previewUrl.value, block: blockId }, targetOrigin)
    }


    function setIframe(iframe) {
        iframeReference.value = iframe
    }

    function showPreview() {
        previewVisible.value = true
    }

    function hidePreview() {
        previewVisible.value = false
    }

    function reset() {
        previewVisible.value = false
        previewUrl.value = null
        iframeReference.value = null
    }

    return {
        url: readonly(previewUrl),
        visible: readonly(previewVisible),
        updatePreview,
        setIframe,
        iframeReference,
        showPreview,
        hidePreview,
        reset,
        locateBlock,
        postToIframe,
    }
});
