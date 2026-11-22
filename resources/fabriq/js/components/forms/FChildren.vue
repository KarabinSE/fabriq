<template>
    <div>
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <h3 class="text-xl font-light ">
                    {{ labels.title }}
                </h3>
            </div>

            <div
                v-show="children.length > 0"
                class="flex justify-end"
            >
                <button
                    :disabled="children.length >= max"
                    class="flex items-center text-sm link disabled:opacity-60"
                    @click="addChild"
                >
                    <PlusIcon
                        v-if="children.length < max"
                        class="size-5 mr-2 "
                    />
                    {{ children.length < max ? labels.add : labels.max }}
                </button>
            </div>
        </div>

        <Draggable
            v-model="children"
            handle=".handle"
            v-bind="{
                ...dragOptions,
                ...$attrs,
            }"
            :item-key="(item) => item.content.id"
            @start="drag = true"
            @end="drag = false"
        >
            <template #item="{element: child, index: childIndex}">
                <UiCard
                    is-child
                    collapsible
                    no-shadow
                    header-classes=" py-1.5"
                >
                    <template #header>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center flex-1 space-x-6">
                                <GripVerticalIcon class="block w-4 h-4 text-gray-400 handle" />
                                <span class="inline-flex text-xs sm:text-sm font-medium text-gray-500">{{ child.name ?? child.heading }}</span>
                            </div>
                            <div class="flex items-center space-x-4">
                                <button
                                    v-if="canClone"
                                    v-tooltip="'Klona'"
                                    class="focus:outline-none"
                                    :disabled="children.length >= max"
                                    @click.stop="cloneChild(child)"
                                >
                                    <CloneIcon
                                        thin
                                        class="h-5"
                                    />
                                </button>
                                <button
                                    v-if="canCopy"
                                    v-tooltip="'Kopiera ID'"
                                    class="focus:outline-none"
                                    type="button"
                                    @click.stop="copyChildId(child.id)"
                                >
                                    <LinkIcon
                                        class="h-5"
                                        thin
                                    />
                                </button>
                                <FButtonSwitch
                                    v-if="canHide"
                                    v-model="child.hidden"
                                    class="self-center mb-1 "
                                />
                                <FConfirmDropdown
                                    confirm-question="Vill du ta bort servicen?"
                                    @confirmed="deleteChild(childIndex)"
                                >
                                    <TrashIcon
                                        class="w-4 mt-1 duration-150 h-5transition-colors hover:text-red-500"
                                        thin
                                    />
                                </FConfirmDropdown>
                                <div class="w-px h-5 mx-2 bg-gray-300" />
                            </div>
                        </div>
                    </template>

                    <slot :child="child" />
                </UiCard>
            </template>
        </Draggable>

        <UiDashedBox
            v-show="children?.length <= 0"
            size="min-h-24"
        >
            <template #header>
                <h4 class="mb-2 text-base font-light">
                    {{ labels.empty }}
                </h4>
            </template>
            <template #link>
                <div class="flex justify-center">
                    <button
                        class="flex items-center text-sm font-semibold focus:outline-none"
                        type="button"
                        @click="addChild"
                    >
                        <PlusIcon class="size-5 mr-2 " />{{ labels.add }}
                    </button>
                </div>
            </template>
        </UiDashedBox>
    </div>
</template>

<script>
import Draggable from 'vuedraggable'
import { useClipboard } from '@vueuse/core'
import { Block } from '@fabriq/models/Block'
import { toast } from '@fabriq/components/toast/toast'

export default {
    name: 'FChildren',
    components: {
        Draggable, 
    },

    inheritAttrs: false,
    props: {
        modelValue: {
            type: Array,
            default: () => [],
        },

        parent: {
            type: Object,
            default: () => ({
            }),
        },

        labels: {
            type: Object,
            default: () => ({
                title: 'Låda',
                add: 'Lägg till låda',
                empty: 'Ingen låda har lagts till ännu',
                max: 'Max antal uppnått',
            }),
        },

        canClone: {
            type: Boolean,
            default: true,
        },

        canCopy: {
            type: Boolean,
            default: true,
        },

        canHide: {
            type: Boolean,
            default: true,
        },

        max: {
            type: Number,
            default: Infinity,
        },

        itemContent: {
            type: Object,
            default: () => ({
            }),
        },

        dragOptions: {
            type: Object,
            default: () => ({
                animation: 200,
                group: 'description',
                disabled: false,
                ghostClass: 'ghost',
            }),
        },
    },

    setup() {
        const { copy } = useClipboard({
            legacy: true, 
        })

        return {
            copy, 
        }
    },

    computed: {
        children() {
            return this.parent.children
        },
    },

    methods: {
        addChild () {
            const child = new Block({
                name: `${this.labels.title} ${((this.children?.length ?? 0) + 1)}`,
                content: this.itemContent,
            })

            this.parent.addChild(child)
        },

        cloneChild(child) {
            this.parent.addChild(child.clone())
        },

        deleteChild (index) {
            this.parent.removeChild(index)
        },

        async copyChildId(id) {
            await this.copy('#' + id)

            toast.success({
                title: `${this.labels.title}-ID har kopierats`,
                message: 'Klistra in som en extern länk i fältet till kontrollen du önskar länka blocket till.', 
            })
        },
    },
}
</script>