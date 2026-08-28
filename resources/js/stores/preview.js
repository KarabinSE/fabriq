import { defineStore } from "pinia";
import { computed, readonly, ref, watch } from "vue";
import { usePageStore } from "./page";
import axios from "axios";
import router from "@/routes/router";

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
    }))

    const page = computed(() => pageStore.page)


    /**
     * whenever we enter a new page, update the preview url. 
     * If there is no preview generated for the page, generate one
     * and update the url again.
     */
    watch(() => page.value.id, async () => {
        if(page.value.id) {
            let newUrl = await getPreviewUrl()
            const hasPreview = !!(new URL(newUrl)).searchParams.get('preview')
            
            if(!hasPreview) {
                await updatePreview()
                newUrl = await getPreviewUrl()
            }
            
            previewUrl.value = newUrl
        }
    })


    async function getPreviewUrl() {
        try {
            const { data } = await axios.post('/api/admin/previews', previewPayload.value)

            return data.preview_url
                
        } catch (error) {
            console.error(error)
        }
    }

    async function updatePreview () {
        try {
            await axios.patch('/api/admin/previews/' + pageStore.page.id, previewPayload.value)

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