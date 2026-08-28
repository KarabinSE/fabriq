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
                    <PlusIcon class="w-5 h-5 mr-2" />Lägg till block
                </button>
            </div>
        </slot>
        <div>
            <div v-if="pageStore.blocks.index.length === 0">
                <div class="flex items-center justify-center h-48 border-2 border-dashed rounded border-royal-200">
                    <div class="flex flex-col items-center">
                        <div class="mb-4 text-xl font-light">
                            Inga block har lagts till ännu
                        </div>
                        <button
                            class="flex items-center text-sm link"
                            @click="showBlockTypeModal"
                        >
                            <PlusIcon class="w-5 h-5 mr-2" />Lägg till block
                        </button>
                    </div>
                </div>
            </div>

            <Draggable
                v-model="pageStore.blocks.index"
                handle=".handle"
                tag="ul"
                v-bind="dragOptions"
                class="list-group"
                @start="drag = true"
                @end="drag = false"
            >
                <TransitionGroup
                    type="transition"
                    :name="'flip-list-move'"
                >
                    <li
                        v-for="(block, boxIndex) in pageStore.blocks.index"
                        :key="'block-' + block.id"
                        class="list-group-item"
                    >
                        <UiCard
                            v-if="block.id"
                            collapsible
                            :identifier="block.id"
                            :open-by-default="block.newlyAdded"
                            :padding="false"
                            header-classes="px-3 @sm/card:px-4 py-3 @sm/card:py-4"
                        >
                            <template #header>
                                <div class="flex items-center justify-between ">
                                    <div class="flex items-center flex-1 min-w-0 gap-2 @2xl:gap-6">
                                        <GripVerticalIcon
                                            v-if="!lockedBlocks"
                                            class="block size-4 @2xl:size-6 text-gray-400 @2xl:text-gray-300 cursor-move handle"
                                        />
                                        <div class="w-full min-w-0 overflow-hidden">
                                            <div class="flex min-w-0 flex-col gap-1">
                                                <div
                                                    class="inline text-xs @2xl:text-sm font-semibold leading-none text-gray-400"
                                                >
                                                    {{ block.block_type.name }}
                                                </div>
                                                <div class="min-w-0 flex-1 overflow-hidden text-ellipsis whitespace-nowrap leading-none text-base font-normal @2xl:font-light @2xl:text-xl">
                                                    {{ block.name }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-4">
                                        <!-- <ellipsis-icon class="w-6 h-6 mr-4" /> -->
                                        <VPopover
                                            v-if="blockBlockType(block).preview_src || blockBlockType(block).base_64_svg"
                                            trigger="hover"
                                            class="hidden @xl:flex"
                                            placement="top"
                                            :delay="{ show: 300, hide: 100 }"
                                        >
                                            <ImageIcon
                                                thin
                                                class="h-8"
                                            />

                                            <template #popover>
                                                <div class="rounded-md border border-gray-200 shadow overflow-hidden bg-white max-w-md">
                                                    <img
                                                        :src="blockBlockType(block).preview_src || `data:image/svg+xml;base64,` + blockBlockType(block).base_64_svg"
                                                    >
                                                </div>
                                            </template>
                                        </VPopover>
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
                                                        @confirmed="deleteBlock(boxIndex)"
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
                                                v-tooltip.bottom="{ delay: { show: 300, hide: 100 }, content: 'Klona block' }"
                                                class="focus:outline-none"
                                                @click.stop="cloneBlock(block)"
                                            >
                                                <CloneIcon
                                                    thin
                                                    class="h-6"
                                                />
                                            </button>
                                            <button
                                                v-tooltip.bottom="{ delay: { show: 300, hide: 100 }, content: 'Kopiera block-ID' }"
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
                                                @confirmed="deleteBlock(boxIndex)"
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
                                            v-tooltip.bottom="{ delay: { show: 300, hide: 100 }, content: 'Skrolla till block' }"
                                            @click.stop="scrollToBlock(block.id)"
                                            class="focus:outline-none"
                                        >
                                            <CrosshairsIcon class="size-6 text-gray-500" thin />
                                        </button>
                                    </div>
                                </div>
                            </template>
                            <div class="px-3 sm:px-4 py-3 sm:py-4">
                                <KeepAlive>
                                    <Component
                                        :is="block.block_type.component_name"
                                        :content="block"
                                        :value="block"
                                        :index="boxIndex"
                                        @repeater-updated="refreshBlock"
                                    />
                                </KeepAlive>
                            </div>
                        </UiCard>
                    </li>
                </TransitionGroup>
            </Draggable>
        </div>
    </div>
</template>

<script>
import ImageIcon from '@/icons/ImageIcon.vue';
import BlockType from '@/models/BlockType';
import { VPopover } from 'v-tooltip';
import Draggable from 'vuedraggable';
import { useConfigStore, usePageStore, usePreviewStore } from '@/stores';
import { useClipboard } from '@vueuse/core';
import CrosshairsIcon from '@/icons/CrosshairsIcon.vue';

export default {
    components: {
        Draggable,
        VPopover
    },

    props: {
        value: {
            type: [Array],
            default: () => [],
        },
        withPreviewBlockLocator: {
            type: Boolean,
            default: false
        }
    },

    emits: ['input'],

    setup() {
        const configStore = useConfigStore();
        const pageStore = usePageStore()
        const previewStore = usePreviewStore()
        const config = useConfigStore()

        const { copy } = useClipboard({ legacy: true });

        return {
            configStore,
            pageStore,
            previewStore,
            config,
            copy,
        }
    },

    data() {
        return {
            blockTypes: {}
        }
    },

    computed: {

        localBlocks: {
            get() {
                return this.value
            },

            set(value) {
                this.$emit('input', value)
            },
        },

        page() {
            return this.configStore.config;
        },

        locale() {
            return this.configStore.activeLocale;
        },

        dragOptions() {
            return {
                animation: 200,
                group: 'description',
                disabled: this.lockedBlocks,
                ghostClass: 'ghost',
            }
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

        activeLocale() {
            return this.configStore.activeLocale;
        },

    },

    async beforeMount() {
        await this.fetchBlockTypes()
    },

    methods: {

        scrollToBlock(blockId) {
            this.previewStore.locateBlock(blockId)
        },

        blockBlockType(block) {
            if (!this.blockTypes || !Array.isArray(this.blockTypes)) {
                return block.block_type
            }

            return this.blockTypes?.find(blockType => block.block_type.id === blockType.id)
        },

        copySuccess() {
            this.$toast.success({
                title: 'Blockets ID har kopierats',
                message: 'Klistra in som en extern länk i fältet till kontrollen du önskar länka blocket till.'
            })
        },

        refreshBlock(payload) {
            this.$emit('input', this.localBlocks)
        },

        showBlockTypeModal() {
            this.$vfm.show('block-type-modal')
        },

        deleteBlock(index) {
            this.pageStore.blocks.remove(index)
        },

        cloneBlock(block) {
            const clonedBlock = structuredClone(block)

            clonedBlock.name = 'Kopia av ' + block.name
            this.pageStore.blocks.add(clonedBlock)
        },

        async fetchBlockTypes() {
            try {
                const { data } = await BlockType.index()

                this.blockTypes = data
            } catch (error) {
                console.error(error)
            }
        },

        async copyBlockId(id) {
            await this.copy('#' + id);

            this.copySuccess()
        }
    },
}
</script>

<style>
    .flip-list-move {
        transition: transform 0.5s;
    }

    .no-move {
        transition: transform 0s;
    }

    .ghost {
        opacity: 0.5;
        /* background: #c8ebfb; */
    }

    .flip-list-move {
        transition: transform 0.5s;
    }

    .no-move {
        transition: transform 0s;
    }

    .ghost,
    .sortable-ghost {
        opacity: 0.5;
    }

    .list-group-item i {
        cursor: pointer;
    }
</style>
