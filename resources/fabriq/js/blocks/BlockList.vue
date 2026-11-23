<template>
    <div class="mt-12 mb-4">
        <slot name="header">
            <div class="flex items-baseline justify-between">
                <h4 class="text-3xl font-light text-gray-700">
                    Block
                </h4>
                <button
                    v-show="!lockedBlocks"
                    class="flex items-center text-sm link"
                    @click="showBlockTypeModal"
                >
                    <PlusIcon class="size-5 mr-2" />Lägg till block
                </button>
            </div>
        </slot>

        <Transition
            mode="out-in"
            name="fade"
        >
            <div
                v-if="pageStore.blocksLoading"
                key="spinner"
                class="flex justify-center items-center h-44"
            >
                <SpinIcon class="size-8 animate-spin" />
            </div>

            <div v-else>
                <div
                    v-if="pageStore.blocks.index.length === 0"
                    class="mt-4 flex items-center justify-center h-48 border-2 border-dashed rounded border-royal-200"
                >
                    <div class="flex flex-col items-center">
                        <div class="mb-4 text-xl font-light">
                            Inga block har lagts till ännu
                        </div>
                        <button
                            class="flex items-center text-sm link"
                            @click="showBlockTypeModal"
                        >
                            <PlusIcon class="size-5 mr-2" />Lägg till block
                        </button>
                    </div>
                </div>

                <Draggable
                    v-else
                    v-model="pageStore.blocks.index"
                    handle=".handle"
                    :item-key="el => el.content.id"
                    v-bind="dragOptions"
                    @start="onDragStart"
                    @end="onDragEnd"
                >
                    <template #item="{element: block, index: blockIndex}">
                        <UiCard
                            :key="block.content.id"
                            collapsible
                            :identifier="block.content.id"
                            :open-by-default="block.newlyAdded"
                            :padding="false"
                            :header-classes="cn('px-3 @sm/card:px-4 py-2 @sm/card:py-3', dragging && 'cursor-grabbing!')"
                        >
                            <template #header>
                                <div class="flex items-center justify-between ">
                                    <div class="flex items-center flex-1 min-w-0 gap-2 @2xl:gap-4">
                                        <div
                                            :class="[
                                                'handle p-1 py-2',
                                                dragging ? 'cursor-grabbing' : 'cursor-grab'
                                            ]"
                                        >
                                            <GripVerticalIcon
                                                v-if="!lockedBlocks"
                                                class="block size-4 @2xl:size-5 text-gray-400 @2xl:text-gray-300"
                                            />
                                        </div>
                                        <div class="w-full min-w-0 overflow-hidden">
                                            <div class="flex min-w-0 flex-col gap-1">
                                                <div
                                                    class="inline text-xs @2xl:text-sm font-medium leading-none text-gray-400"
                                                >
                                                    {{ block.block_type.name }}
                                                </div>
                                                <div class="min-w-0 flex-1 truncate leading-tight text-sm @2xl:font-light @2xl:text-lg pr-2">
                                                    {{ block.name }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-4">
                                        <UiPopover
                                            v-if="getBlockType(block).preview_src || getBlockType(block).base_64_svg"
                                            :triggers="['hover']"
                                            class="hidden @xl:flex"
                                            placement="top"
                                            :delay="{ show: 300, hide: 100 }"
                                        >
                                            <ImageIcon
                                                thin
                                                class="h-8"
                                            />

                                            <template #popper>
                                                <div class="rounded-md border border-gray-200 shadow overflow-hidden bg-white max-w-md">
                                                    <img
                                                        :src="getBlockType(block).preview_src || `data:image/svg+xml;base64,` + getBlockType(block).base_64_svg"
                                                    >
                                                </div>
                                            </template>
                                        </UiPopover>
                                        <div v-if="lockedBlocks">
                                            <LockIcon class="h-5 md:h-6" />
                                        </div>
                                        <div
                                            v-if="lockedBlocks"
                                            class="w-px h-7 md:h-8 mx-6 bg-gray-300"
                                        />

                                        <div class="@xl:hidden flex items-center gap-4">
                                            <UiDropdown alignment="top-right">
                                                <GearIcon
                                                    class="h-5 text-gray-300!"
                                                    thin
                                                />
                                                <template #dropdown>
                                                    <button
                                                        v-show="!lockedBlocks"
                                                        class="focus:outline-none flex gap-4 items-center py-3 px-4 whitespace-nowrap text-sm w-44"
                                                        @click.stop="cloneBlock(block)"
                                                    >
                                                        <CloneIcon
                                                            thin
                                                            class="h-5"
                                                        />
                                                        Klona block
                                                    </button>
                                                    <button
                                                        class="focus:outline-none flex gap-4 items-center py-3 px-4 whitespace-nowrap text-sm w-44"
                                                        type="button"
                                                        @click.stop="copyBlockId(block.id)"
                                                    >
                                                        <LinkIcon
                                                            class="h-4"
                                                            thin
                                                        />
                                                        Kopiera block-ID
                                                    </button>

                                                    <FConfirmDropdown
                                                        v-show="!lockedBlocks"
                                                        confirm-question="Vill du ta bort detta blocket?"
                                                        class="relative"
                                                        @confirmed="deleteBlock(block)"
                                                    >
                                                        <div
                                                            class="flex gap-4 items-center py-3 px-4 whitespace-nowrap text-sm w-44"
                                                        >
                                                            <TrashIcon
                                                                class="h-5"
                                                                thin
                                                            />
                                                            Radera
                                                        </div>
                                                    </FConfirmDropdown>
                                                </template>
                                            </UiDropdown>

                                            <FButtonSwitch
                                                v-model="block.hidden"
                                                class="self-center mb-1 [&_svg]:h-5"
                                            />
                                        </div>
                                        <div class="hidden @xl:flex items-center space-x-4">
                                            <button
                                                v-show="!lockedBlocks"
                                                v-tooltip="'Klona block'"
                                                class="focus:outline-none"
                                                @click.stop="cloneBlock(block)"
                                            >
                                                <CloneIcon
                                                    thin
                                                    class="h-6"
                                                />
                                            </button>
                                            <button
                                                v-tooltip="'Kopiera block-ID'"
                                                class="focus:outline-none"
                                                type="button"
                                                @click.stop="copyBlockId(block.id)"
                                            >
                                                <LinkIcon
                                                    class="h-6"
                                                    thin
                                                />
                                            </button>
                                            <FButtonSwitch
                                                v-model="block.hidden"
                                                class="self-center mb-1 "
                                            />
                                            <FConfirmDropdown
                                                v-show="!lockedBlocks"
                                                confirm-question="Vill du ta bort detta blocket?"
                                                class="relative w-6 h-6"
                                                @confirmed="deleteBlock(block)"
                                            >
                                                <TrashIcon
                                                    class="h-6 transition-colors duration-150 hover:text-red-500"
                                                    thin
                                                />
                                            </FConfirmDropdown>
                                        </div>
                                        <div class="w-px h-8 mx-3 bg-gray-300" />
                                        <button
                                            v-if="withPreviewBlockLocator"
                                            v-tooltip="'Skrolla till block'"
                                            class="focus:outline-none"
                                            @click.stop="scrollToBlock(block)"
                                        >
                                            <CrosshairsIcon
                                                class="size-6 text-gray-500"
                                                thin
                                            />
                                        </button>
                                    </div>
                                </div>
                            </template>
                            <div class="px-3 sm:px-4 py-3 sm:py-4">
                                <KeepAlive>
                                    <Component
                                        :is="block.block_type.component_name"
                                        v-model:name="block.name"
                                        v-model:content="block.content"
                                        v-model:children="block.content"
                                        :block
                                        :index="blockIndex"
                                    />
                                </KeepAlive>
                            </div>
                        </UiCard>
                    </template>
                </Draggable>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import ImageIcon from '@fabriq/icons/ImageIcon.vue'
import BlockType from '@fabriq/models/BlockType'
import { useConfigStore, usePageStore, usePreviewStore } from '@fabriq/stores'
import { useClipboard } from '@vueuse/core'
import CrosshairsIcon from '@fabriq/icons/CrosshairsIcon.vue'
import { toast } from '@fabriq/components/toast/toast'
import { onBeforeMount, ref } from 'vue'
import { computed } from 'vue'
import BlockTypeModal from '@fabriq/pages/BlockTypeModal.vue'
import Draggable from 'vuedraggable'
import { cn, createModal } from '@fabriq/helpers/utils'

const configStore = useConfigStore()
const pageStore = usePageStore()
const previewStore = usePreviewStore()

const props = defineProps({
    withPreviewBlockLocator: {
        type: Boolean,
        default: false,
    },
})


const { copy } = useClipboard({
    legacy: true,
})

const blockTypes = ref({
})
const dragging = ref(false)

const dragOptions = computed(() => ({
    animation: 200,
    group: 'blocks',
    disabled: lockedBlocks.value,
    ghostClass: 'ghost',
}))

const lockedBlocks = computed(() => {
    if (configStore.devMode) {
        return false
    }

    return pageStore?.page?.template?.data?.locked ?? false
})

onBeforeMount(async() => {
    await fetchBlockTypes()
})

function scrollToBlock(block) {
    previewStore.locateBlock(block.content.id)
}

function getBlockType(block) {
    if (!blockTypes.value || !Array.isArray(blockTypes)) {
        return block.block_type
    }

    return blockTypes.value?.find(blockType => block.block_type.id === blockType.id)
}

function showBlockTypeModal() {
    createModal(BlockTypeModal)
}

function deleteBlock(block) {
    pageStore.blocks.remove(block)
}

function cloneBlock(block) {
    pageStore.blocks.clone(block)
}

async function fetchBlockTypes() {
    try {
        const { data } = await BlockType.index()

        blockTypes.value = data
    } catch (error) {
        console.error(error)
    }
}

async function copyBlockId(id) {
    try {
        await copy('#' + id)
        toast.success({
            title: 'Blockets ID har kopierats',
            message: 'Klistra in som en extern länk i fältet till kontrollen du önskar länka blocket till.',
        })
    } catch (error) {
        toast.error({
            title: 'Ojdå! Det gick inte att kopiera ID',
        })
        console.error(error)
    }
}

function onDragStart() {
    dragging.value = true
    document.body.classList.add('cursor-grabbing')
}

function onDragEnd() {
    dragging.value = false
    document.body.classList.remove('cursor-grabbing')
}
</script>
