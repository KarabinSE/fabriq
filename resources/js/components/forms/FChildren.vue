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
                        class="w-5 h-5 mr-2 "
                    />
                    {{ children.length < max ? labels.add : labels.max }}
                </button>
            </div>
        </div>

        <Draggable
            v-model="children"
            handle=".handle"
            tag="ul"
            v-bind="dragOptions"
            class="list-group"
            :group="{ name: 'children', pull: 'clone', put: ['children'] }"
            @start="drag = true"
            @end="drag = false"
        >
            <UiCard
                v-for="(child, childIndex) in children"
                :key="'child' + (child.id ?? childIndex)"
                is-child
                collapsible
                no-shadow
                header-classes="bg-gray-50 py-2"
            >
                <template #header>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center flex-1 space-x-6">
                            <GripVerticalIcon class="block w-4 h-4 text-gray-400 handle" />
                            <span class="inline-flex text-sm font-semibold text-gray-500">{{ child.name ?? child.heading }}</span>
                        </div>
                        <div class="flex items-center space-x-4">
                            <button
                                v-if="canClone"
                                v-tooltip.bottom="{ delay: { show: 300, hide: 100 }, content: 'Klona' }"
                                class="focus:outline-none"
                                @click.stop="cloneChild(child)"
                            >
                                <CloneIcon
                                    thin
                                    class="h-6"
                                />
                            </button>
                            <button
                                v-if="canCopy"
                                v-tooltip.bottom="{ delay: { show: 300, hide: 100 }, content: 'Kopiera ID' }"
                                class="focus:outline-none"
                                type="button"
                                @click.stop="copyChildId(child.id)"
                            >
                                <LinkIcon
                                    class="h-6"
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
                                    class="w-5 mt-1 duration-150 h-5transition-colors hover:text-red-500"
                                    thin
                                />
                            </FConfirmDropdown>
                            <div class="w-px h-8 mx-6 bg-gray-300" />
                        </div>
                    </div>
                </template>

                <slot :child="child" />
            </UiCard>
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
                        <PlusIcon class="w-5 h-5 mr-2 " />{{ labels.add }}
                    </button>
                </div>
            </template>
        </UiDashedBox>
    </div>
</template>

<script>
import Draggable from 'vuedraggable';
import { useClipboard } from '@vueuse/core';

export default {
    name: 'FChildren',
    components: { Draggable },
    props: {
        value: {
            type: Array,
            default: () => []
        },
        labels: {
            type: Object,
            default: () => ({
                title: 'Låda',
                add: 'Lägg till låda',
                empty: 'Ingen låda har lagts till ännu',
                max: 'Max antal uppnått'
            })
        },
        canClone: {
            type: Boolean,
            default: false,
        },
        canCopy: {
            type: Boolean,
            default: false,
        },
        canHide: {
            type: Boolean,
            default: false,
        },
        max: {
            type: Number,
            default: Infinity
        },
        itemContent: {
            type: Object,
            default: () => ({})
        },
        dragOptions: {
            type: Object,
            default: () => ({
                animation: 200,
                group: 'description',
                disabled: false,
                ghostClass: 'ghost'
            })
        }
    },
    setup() {
        const { copy } = useClipboard({ legacy: true });

        return { copy }
    },
    data() {
        return  {
            children: this.value
        }
    },
    watch: {
        children() {
            this.$emit('input', this.children)
        }
    },
    mounted () {
        if (!this.value) {
            this.$emit('input', this.children)
        } else {
            this.children = this.value
        }
    },
    methods: {
        addChild () {
            let newItem =  {
                id: 'i' + Math.random().toString(20).substr(2, 6),
                heading: this.labels.title + ' ' + ((this.children?.length ?? 0) + 1),
                newlyAdded: true,
                ...this.itemContent,
            }

            this.children.push(newItem)
            this.$nextTick(() => {
                newItem.newlyAdded = false
            })
        },
        cloneChild(child) {
            const newItem = {
                ...structuredClone(child),
                id: 'i' + Math.random().toString(20).substr(2, 6),
                heading: 'Kopia av ' + child.heading,
            }

            this.children.push(newItem)
            this.$nextTick(() => {
                newItem.newlyAdded = false
            })
        },
        deleteChild (index) {
            this.children.splice(index, 1)
        },
        copySuccess () {
            this.$toast.success({ title: `${this.labels.title}-ID har kopierats`, message: 'Klistra in som en extern länk i fältet till kontrollen du önskar länka blocket till.' })
        },
        async copyChildId(id) {
            await this.copy('#' + id);

            this.copySuccess()
        }
    }
}
</script>