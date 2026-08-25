<template>
    <div class="flex flex-col gap-6">
        <div class="flex justify-between gap-12">
            <FInput
                v-model="localContent.name"
                name="name"
                class="basis-120"
                label="Namn"
                rules="required"
                help-text="Visas endast internt"
            />
        </div>

        <hr class="w-full">

        <div>
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <h3 class="text-xl font-light ">
                        Kort
                    </h3>
                </div>
                <div
                    v-show="localContent.children.length > 0"
                    class="flex justify-end"
                >
                    <span>
                        <button
                            class="flex items-center text-sm link"
                            @click="addCard"
                        >

                            <PlusIcon class="w-5 h-5 mr-2 " />Lägg till kort
                        </button>
                    </span>
                </div>
            </div>

            <Draggable
                v-model="localContent.children"
                handle=".handle"
                tag="ul"
                v-bind="dragOptions"
                class="list-group"
                :group="{ name: 'children', pull: 'clone', put: ['children'] }"
                @start="drag = true"
                @end="drag = false"
            >
                <UiCard
                    v-for="(child, childIndex) in localContent.children"
                    :key="'child' + childIndex"
                    is-child
                    collapsible
                    class="mb-6"
                    no-shadow
                    header-classes="bg-gray-50 py-2"
                >
                    <template #header>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center flex-1 space-x-6">
                                <GripVerticalIcon class="block w-4 h-4 text-gray-400 handle" />
                                <span class="inline-flex text-sm font-semibold text-gray-500">{{ child.heading }}</span>
                            </div>
                            <div class="flex items-center space-x-4">
                                <button
                                    v-tooltip.bottom="{ delay: { show: 300, hide: 100 }, content: 'Klona block' }"
                                    class="focus:outline-none"
                                    @click.stop="addCard(child)"
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
                                    @click.stop="copyBlockId(child.id)"
                                >
                                    <LinkIcon
                                        class="h-6"
                                        thin
                                    />
                                </button>
                                <FButtonSwitch
                                    v-model="child.hidden"
                                    class="self-center mb-1 "
                                />
                                <FConfirmDropdown
                                    confirm-question="Vill du ta bort detta kortet?"
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
                    <div
                        class="grid grid-cols-3 gap-x-6 gap-y-6"
                    >
                        <FInput
                            v-model="child.heading"
                            label="Rubriktext"
                            help-text="Obs, denna rubrik syns utåt"
                            name="header"
                        />
                        <FEditor
                            v-model="child.body"
                            label="Text"
                            class="col-span-3"
                        />
                    </div>
                </UiCard>
            </Draggable>

            <UiDashedBox
                v-show="localContent.children.length <= 0"
                size="min-h-24"
            >
                <template #header>
                    <h4 class="mb-2 text-base font-light">
                        Inget kort har lagts till ännu
                    </h4>
                </template>
                <template #link>
                    <div class="flex justify-center">
                        <button
                            class="flex items-center text-sm font-semibold focus:outline-none"
                            type="button"
                            @click="addCard"
                        >
                            <PlusIcon class="w-5 h-5 mr-2 " />Lägg till kort
                        </button>
                    </div>
                </template>
            </UiDashedBox>
        </div>
    </div>
</template>
<script>
import Draggable from 'vuedraggable'
import { useClipboard } from '@vueuse/core';

export default {
    name: 'CardGridBlock',
    components: { Draggable },
    props: {
        index: {
            type: Number,
            default: 0
        },
        value: {
            type: [String, Number, Object, Array],
            default: () => {
                return {
                    name: '',
                    header: ''
                }
            }
        },
        content: {
            type: [Array, Object],
            required: true,
            default: () => {
                return {
                    name: '',
                    headerType: {
                        name: ''
                    }
                    // repeaters: []
                }
            }
        }
    },
    setup() {
        const { copy } = useClipboard({ legacy: true });

        return { copy }
    },
    data () {
        return {
            headerTypes: [
                {
                    text: 'Heading 1',
                    value: 'h1'
                },
                {
                    text: 'Heading 2',
                    value: 'h2'
                },
                {
                    text: 'Heading 3',
                    value: 'h3'
                }
            ],
            // repeaters: this.content.repeaters,
            localContent: this.content
        }
    },
    computed: {
        dragOptions () {
            return {
                animation: 200,
                group: 'description',
                disabled: false,
                ghostClass: 'ghost'
            }
        }
    },
    mounted () {
        this.$set(this.localContent, 'button', { text: '', linkType: 'internal', page_id: null })
    },
    methods: {
        addCard (item) {
            let newItem = {}
            if (!item.heading) {
                newItem = {
                    id: 'i' + Math.random().toString(20).substr(2, 6),
                    header: 'Kort ' + (this.localContent.children.length + 1),
                    newlyAdded: true
                }
            } else {
                newItem = JSON.parse(JSON.stringify(item))

                newItem.id = 'i' + Math.random().toString(20).substr(2, 6)
                newItem.heading = 'Kopia av ' + newItem.heading
            }
            this.localContent.children.push(newItem)
            this.$nextTick(() => {
                newItem.newlyAdded = false
            })
        },
        deleteChild (index) {
            this.localContent.children.splice(index, 1)
        },
        copySuccess () {
            this.$toast.success({ title: 'Kortets ID har kopierats', message: 'Klistra in som en extern länk i fältet till kontrollen du önskar länka blocket till.' })
        },
        async copyBlockId(id) {
            await this.copy('#' + id);

            this.copySuccess()
        },
    }
}
</script>
