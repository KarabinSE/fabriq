<template>
    <Transition
        name="preview-shell"
        :duration="{ enter: 600, leave: 500 }"
        @after-enter="setPreviewIframe"
    >
        <div
            v-if="previewUrl && previewVisible"
            class="fixed inset-0 bg-gray-50 z-50 transition-colors duration-500 @container/preview overflow-y-auto"
        >
            <div class="flex flex-col size-full">
                <Transition
                    appear
                    name="slide-top"
                >
                    <div class="top-0 preview-panel-top min-h-20 bg-white shadow-md p-5 flex flex-col gap-5 @xl:flex-row justify-between">
                        <div class="flex items-center gap-2 min-w-0 h-10">
                            <div
                                :class="page.published ? 'bg-green-400' : 'bg-gray-300'"
                                class="size-2 aspect-square rounded-full transition-colors"
                            />
                            <h1 class="text-xl @xl:text-3xl whitespace-nowrap overflow-hidden text-ellipsis">{{ page.name }}</h1>
                        </div>

                        <div class="flex gap-4">
                            <button
                                @click="closePreview"
                                spinner-color="text-royal-500"
                                class="flex-1 px-6 py-2.5 leading-none text-sm fabriq-btn btn-outline-royal"
                            >
                                Stäng
                            </button>

                            <FButton
                                :click="page.published ? publishPage : updateContent"
                                class="flex-1 px-6 py-2.5 leading-none text-sm fabriq-btn btn-royal whitespace-nowrap"
                            >
                                <span v-text="page.published ? 'Spara & publicera' : pageStore.wasInitiallyPublished ? 'Avpublicera & spara utkast' : 'Spara utkast'" />
                            </FButton>
                        </div>
                    </div>
                </Transition>

                <div class="px-5 pt-12 flex-1 min-h-0">
                    <div class="size-full flex flex-col @4xl/preview:flex-row gap-x-2" >

                        <Transition
                            appear
                            name="slide-left"
                        >
                            <div
                                class="preview-panel-left flex flex-col @4xl/preview:flex-1 @4xl/preview:min-w-sm"
                            >
                                <div class="flex items-baseline justify-between pr-5 pb-4">
                                    <h4 class="text-3xl font-light text-gray-700">
                                        Block
                                    </h4>
                                    <button
                                        v-show="!lockedBlocks"
                                        class="flex items-center text-sm link"
                                        @click="showBlockTypeModal"
                                    >
                                        <PlusIcon class="w-5 h-5 mr-2" />Lägg till block
                                    </button>
                                </div>
                                <div class="overflow-y-auto flex-1 scrollbar-gutter-stable pr-2">
                                    <BlockList
                                        with-preview-block-locator
                                        class="-mt-4! pb-10"
                                    >
                                        <template #header ><span /></template>
                                    </BlockList>
                                </div>
                            </div>
                        </Transition>

                        <Transition
                            appear
                            name="slide-right"
                        >
                            <div class="flex preview-panel-right relative @4xl/preview:min-w-sm">
                                <!-- resize handle -->
                                <div
                                    @mousedown="startResize"
                                    class="hidden @4xl/preview:flex items-center cursor-ew-resize pr-1.5 group"
                                >
                                    <div class="h-10 w-1.5 rounded-md bg-gray-200 group-hover:bg-gray-300 transition-colors"
                                         ref="resizeHandle" />
                                </div>

                                <div
                                    class=" flex flex-col pb-5 flex-1 h-dvh @4xl/preview:h-auto  pt-5 @4xl/preview:pt-0"
                                    :style="previewStyles"
                                >
                                    <div class="flex items-baseline justify-between mb-4">
                                        <h4 class="text-3xl font-light text-gray-700">
                                            Förhandsgranskning
                                        </h4>
                                    <!-- <button
                                        class="flex items-center text-sm link"
                                        @click="createPopout"
                                    >
                                        <ExternalLinkIcon class="size-4.5 mr-2" />Öppna i separat fönster
                                    </button> -->
                                    </div>

                                    <div
                                        class="rounded-lg border border-gray-300 shadow-xl flex-1 bg-white overflow-hidden"
                                    >
                                        <iframe
                                            ref="previewIframe"
                                            :src="previewUrl"
                                            class="size-full"
                                            title="Preview"
                                        />
                                    </div>
                                </div>
                            </div>
                        </Transition>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>
<script>
import { useConfigStore, usePageStore, usePreviewStore, useUiStore } from '@/stores';
import { getCurrentInstance } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import BlockList from '@/blocks/BlockList.vue';
import ExternalLinkIcon from '@/icons/ExternalLinkIcon.vue';
import { useStorage } from '@vueuse/core';

export default {
    name: 'PagePreview',
    components: {
        BlockList,
    },

    setup () {
        const pageStore = usePageStore();
        const configStore = useConfigStore();
        const uiStore = useUiStore();
        const previewStore = usePreviewStore();

        const instance = getCurrentInstance();

        const debouncedUpdatePreview = useDebounceFn(() => {
            instance?.proxy?.updatePreview()
        }, 500)

        const resizeStorage = useStorage('preview-resize-width', { width: 700 })

        return {
            pageStore,
            configStore,
            uiStore,
            previewStore,
            debouncedUpdatePreview,
            resizeStorage
        }
    },

    data() {
        return {
            previewStyles: {
                width: this.resizeStorage.width + 'px'
            },
            popoutWindow: null,
            dragging: false
        }
    },
    
    computed: {
        page() {
            return this.pageStore.page;
        },
        previewUrl() {
            return this.previewStore.url
        },
        previewVisible() {
            return this.previewStore.visible
        },
        localizedContent() {
            return this.pageStore.localizedContent
        },

        lockedBlocks() {
            if (this.devMode) {
                return false
            }

            return this.page.locked
        },

        devMode() {
            return this.configStore.devMode;
        },

    },

    watch: {
        localizedContent: {
            deep: true,
            immediate: false,
            handler(_, from ) {
                if(this.previewVisible){
                    this.debouncedUpdatePreview()
                }
            },
        },
    },

    mounted() {
        this.setPreviewIframe()
    },

    methods: {
        async publishPage() {
            this.$emit('publish')
        },
        async updateContent() {
            this.$emit('save')
        },


        calcResize(event) {
            const mouseX = event.pageX

            const padding = 29 // gaps and padding etc. To align mouse with handle
            const newWidth = window.innerWidth - mouseX - padding

            // just use min-w on the elements to clamp it..
            this.previewStyles.width = `${newWidth}px`
            this.resizeStorage.width = newWidth
        },

        startResize() {
            document.body.classList.add('cursor-ew-resize')
            this.$refs.resizeHandle.classList.add('bg-gray-400!')
            this.$refs.resizeHandle.classList.add('select-none')
            window.addEventListener('mousemove', this.calcResize)
            window.addEventListener('mouseup', this.stopResize)
        },
        
        stopResize () {
            document.body.classList.remove('cursor-ew-resize')
            this.$refs.resizeHandle.classList.remove('bg-gray-400!')
            this.$refs.resizeHandle.classList.remove('select-none')
            window.removeEventListener('mousemove', this.calcResize)
            window.removeEventListener('mouseup', this.stopResize)
        },

        showBlockTypeModal() {
            this.$vfm.show('block-type-modal')
        },

        closePreview() {
            this.previewStore.hidePreview()
        },

        setPreviewIframe() {
            this.previewStore.setIframe(this.$refs.previewIframe)
        },

        createPopout() {
            /**
             * cant post message to cross origin
             */
            // this.popoutWindow = window.open(this.previewStore.url, `Förhandsgranskning`, 'popup')
            // this.previewStore.setIframe(this.popoutWindow)
            // this.previewStore.toggleVisible()
        },

        async updatePreview () {
            await this.previewStore.updatePreview()
            this.previewStore.postToIframe()
        },
    }
}
</script>
<style scoped>
.preview-shell-enter-active {
    transition: opacity 200ms ease;
}

.preview-shell-enter {
    opacity: 0;
}

.preview-shell-leave-active {
    transition: opacity 500ms ease;
}

.preview-shell-leave-to {
    opacity: 0;
}

.preview-shell-leave-active .preview-panel-top,
.preview-shell-leave-active .preview-panel-left,
.preview-shell-leave-active .preview-panel-right {
    opacity: 0;
    transition: opacity 300ms ease-out, transform 600ms ease-out;
}

.preview-shell-leave-active .preview-panel-top {
    transform: translateY(-100%);
}

.preview-shell-leave-active .preview-panel-left {
    transform: translateX(-5rem);
}

.preview-shell-leave-active .preview-panel-right {
    transform: translateX(5rem);
}

.slide-top-enter-active,
.slide-top-leave-active {
    transition: opacity 500ms ease, transform 500ms ease;
}
.slide-left-enter-active,
.slide-left-leave-active,
.slide-right-enter-active,
.slide-right-leave-active {
    transition: opacity 300ms ease, transform 300ms ease;
}

.slide-top-enter-active,
.slide-right-leave-active {
    transition-delay: 100ms;
}

.slide-right-enter-active,
.slide-top-leave-active,
.slide-left-enter-active,
.slide-left-leave-active {
    transition-delay: 200ms;
}

.slide-top-enter {
    opacity: 0;
    transform: translateY(-3rem);
}

.slide-top-leave-to {
    opacity: 1;
    transform: translateY(3rem);
}

.slide-left-enter {
    opacity: 0;
    transform: translateX(-3rem);
}

.slide-left-leave-to {
    opacity: 1;
    transform: translateX(3rem);
}

.slide-right-enter {
    opacity: 0;
    transform: translateX(3rem);
}

.slide-right-leave-to {
    opacity: 1;
    transform: translateX(-5rem);
}

@media (prefers-reduced-motion: reduce) {
    .preview-shell-enter-active,
    .preview-shell-leave-active,
    .slide-top-enter-active,
    .slide-top-leave-active,
    .slide-left-enter-active,
    .slide-left-leave-active,
    .slide-right-enter-active,
    .slide-right-leave-active {
        transition-duration: 0.01ms;
        transition-delay: 0ms;
    }
}
</style>
