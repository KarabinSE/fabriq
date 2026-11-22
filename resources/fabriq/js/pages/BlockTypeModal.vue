<template>
    <FModal
        name="block-type-modal"
        :click-to-close="false"
        :overflow="largeBlockPicker ? 'overflow-y-auto' : 'overflow-y-visible'"
        :width="largeBlockPicker ? 'max-w-6xl' : 'max-w-3xl'"
        @before-open="fetchBlockTypes"
    >
        <template #title>
            <span
                class="text-gray-700 fd"
                v-text="'Lägg till block'"
            />
        </template>
        <template #actions>
            <div class="flex justify-end space-x-4">
                <button
                    class="px-8 py-2.5 leading-none fabriq-btn btn-link"
                    @click="closeModal"
                >
                    Stäng
                </button>
                <FButton
                    without-loader
                    :click="setBlockType"
                    class="px-8 py-2.5 leading-none fabriq-btn btn-royal"
                >
                    Lägg till
                </FButton>
            </div>
        </template>
        <div class="relative z-40 py-2">
            <form
                class="grid grid-cols-1 gap-4 sm:grid-cols-2"
                @submit.prevent="setBlockType"
            >
                <FSelect
                    v-if="!largeBlockPicker"
                    ref="selectInput"
                    v-model="chosenBlock.blockType"
                    label="Blocktyp"
                    :clearable="false"
                    name="blockType"
                    rules="required"
                    :options="computedBlockTypes"
                    searchable
                    object
                    value-key="id"
                    option-label="name"
                />
                <FInput
                    v-if="largeBlockPicker"
                    v-model="chosenBlock.blockType.name"
                    name="blockType"
                    rules="required|min:2"
                    class="hidden"
                    label="Blocktyp"
                />
                <FInput
                    ref="nameInput"
                    v-model="chosenBlock.name"
                    name="name"
                    rules="required"
                    label="Namn"
                />
                <div
                    v-if="largeBlockPicker"
                    class="grid grid-cols-4 col-span-2 gap-4"
                >
                    <div
                        v-show="recommendedBlockTypes.length > 0"
                        class="col-span-4 -mb-4"
                    >
                        <FLabel>Rekommenderade block</FLabel>
                    </div>

                    <div
                        v-for="blockType in recommendedBlockTypes"
                        :key="'r' + blockType.id"
                        :class="blockType.id === chosenBlock.blockType.id ? '  border-royal-500  bg-royal-50' : 'opacity-60 border-white'"
                        class="flex flex-col items-center p-3 pb-1 transition-all duration-200 border rounded-md cursor-pointer"
                        @click="selectBlock(blockType)"
                    >
                        <img
                            :src="blockType.thumb_src || `data:image/svg+xml;base64,` + blockType.base_64_svg"
                            class="w-full"
                            alt=""
                        >
                        <div class="mt-2 text-xs">
                            {{ blockType.name }}
                        </div>
                    </div>

                    <div class="col-span-4 -mb-4">
                        <FLabel>
                            {{ recommendedBlockTypes.length > 0 ? 'Övriga' : 'Välj blocktyp' }}
                        </FLabel>
                    </div>

                    <div
                        v-for="blockType in computedBlockTypes"
                        :key="blockType.id"
                        :class="blockType.id === chosenBlock.blockType.id ? '  border-royal-500  bg-royal-50' : 'opacity-60 border-white'"
                        class="flex flex-col items-center p-3 pb-1 transition-all duration-200 border rounded-md cursor-pointer"
                        @click="selectBlock(blockType)"
                    >
                        <img
                            :src="blockType.thumb_src || `data:image/svg+xml;base64,` + blockType.base_64_svg"
                            class="w-full"
                            alt=""
                        >
                        <div class="mt-2 text-xs">
                            {{ blockType.name }}
                        </div>
                    </div>
                </div>

                <button
                    class="hidden"
                    type="submit"
                    :click="setBlockType"
                />
            </form>
        </div>
    </FModal>
</template>

<script setup>
defineOptions({
    name: 'BlockTypeModal', 
})

import Page from '@fabriq/models/BlockType.js'
import { useConfigStore, usePageStore } from '@fabriq/stores'
import { computed, watch, ref } from 'vue'

const configStore = useConfigStore()
const pageStore = usePageStore()

const emits = defineEmits(['close-modal'])
const closeModal = () => emits('close-modal')

const props = defineProps({
    item: {
        type: Object,
        default: () => {},
    },
})

const blockTypes = ref([])
const chosenBlock = ref({
    name: '',
    blockType: null,
})

const blockNames = computed(() => blockTypes.value.map(item => item.name))
const computedBlockTypes = computed(() => {
    const firstFilter = blockTypes.value.filter(item => {
        if (! item.options) {
            return true
        }

        const hiddenFor = item.options.hidden_for || []
        const visibleFor = item.options.visible_for || []
        const recommendedFor = item.options.recommended_for || []

        if (
            hiddenFor.length === 0 &&
            visibleFor.length === 0 &&
            recommendedFor.length === 0
        ) {
            return true
        }

        // If visible_for is defined and template slug is not present
        if (visibleFor.length > 0 && !visibleFor.includes(pageStore.page.template.data.slug)) {
            return false
        }

        if (hiddenFor.includes(pageStore.page.template.data.slug)) {
            return false
        }

        if (recommendedFor.includes(pageStore.page.template.data.slug)) {
            return false
        }

        return true
    })

    return firstFilter
})
const recommendedBlockTypes = computed(() => {
    return blockTypes.value.filter(item => {
        const recommendedFor = item.options?.recommended_for || []

        return recommendedFor.includes(pageStore.page.template.data.slug)
    })
})
const largeBlockPicker = computed(() => {
    if( !configStore.config.ui ) {
        return false
    }

    return !!configStore.config.ui.large_block_picker
})

async function fetchBlockTypes () {
    try {
        const payload = {
            params: {
                field: 'id,name,component_name,has_children,base_64_svg,options',
            },
        }
        const { data } = await Page.index(payload)

        blockTypes.value = data
    } catch (error) {
        console.error(error)
    }
}

async function setBlockType () {
    // TODO: Implement new validation in Vue 3
    pageStore.blocks.add({
        ...chosenBlock.value.blockType,
        name: chosenBlock.value.name,
    })

    closeModal()
}

watch(() => chosenBlock.value.blockType, (blockType) => {
    if (!chosenBlock.value.name || blockNames.value.includes(chosenBlock.value.name)) {
        chosenBlock.value.name = blockType?.name
    }
})
</script>
