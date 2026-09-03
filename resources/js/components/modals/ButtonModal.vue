<template>
    <FModal
        v-model="show"
        :name="id"
        :click-to-close="true"
        :esc-to-close="localButton.linkType !== 'file'"
        overflow="overflow-y-visible"
        width="max-w-xl"
        @before-open="fetchPages"
        @closed="resetModal"
    >
        <template #title>
            <span
                class="text-gray-700 fd"
                v-text="'Redigera knapp'"
            />
        </template>

        <template #actions>
            <div class="flex justify-end space-x-4">
                <button
                    class="px-8 py-2.5 leading-none fabriq-btn btn-link"
                    @click="show = false"
                >
                    Stäng
                </button>
                <FButton
                    without-loader
                    :click="saveButton"
                    class="px-8 py-2.5 leading-none fabriq-btn btn-royal"
                >
                    Spara
                </FButton>
            </div>
        </template>

        <div class="relative z-40 py-2">
            <form
                class="flex flex-col gap-6"
                @submit.prevent="saveButton"
            >
                <FInput
                    v-model="localButton.text"
                    :disabled="disabled"
                    label="Knapptext"
                />
                <FInput
                    v-model="localButton.linkType"
                    input-type="radio"
                    class="col-span-4 xl:col-span-3"
                    :disabled="disabled"
                    name="linkType"
                    :options="linkTypeOptions"
                    label="Länktyp"
                />
                <FInput
                    v-if="localButton.linkType === 'external'"
                    v-model="localButton.url"
                    class="h-18"
                    :disabled="disabled"
                    label="Länk"
                    placeholder="https://exempel.se eller /min-sida"
                />
                <FSelect
                    v-if="localButton.linkType === 'internal'"
                    v-model="localButton.page_id"
                    class="h-18"
                    value-key="id"
                    :reduce-fn="page => page.id"
                    label="Sida"
                    :options="localPages"
                    option-label="name"
                    :disabled="disabled"
                    name="internal_page"
                    @search-focus="checkForPages"
                    @open="checkForPages"
                >
                    <template #prefix="option">
                        <div
                            v-for="pageIndex in option.depth"
                            :key="pageIndex"
                        >
                            <div class="mr-3" />
                        </div>
                    </template>
                </FSelect>

                <FFileInput
                    v-if="localButton.linkType === 'file'"
                    v-model="localButton.file"
                    class="h-18"
                    :disabled="disabled"
                    label="Fil"
                />

                <template v-else>
                    <FColorPicker
                        v-if="color"
                        class="w-full"
                        v-model="localButton.color"
                        :disabled="disabled"
                    />
                    <FArrowPicker
                        v-if="arrow"
                        class="w-full"
                        v-model="localButton.arrow"
                        :disabled="disabled"
                    />
                </template>

                <button
                    class="hidden"
                    type="submit"
                />
            </form>
        </div>
    </FModal>
</template>

<script>
import FArrowPicker from '@/components/forms/FArrowPicker.vue'
import { linkTypeOptions } from '@/components/forms/FButtonItem.vue'
import FColorPicker from '@/components/forms/FColorPicker.vue'
import PageTree from '@/models/PageTree'

export default {
    name: 'ButtonModal',
    components: {
        FArrowPicker,
        FColorPicker,
    },
    props: {
        id: {
            type: String,
            required: true
        },
        value: {
            required: true,
            type: Object,
        },
        color: {
            type: Boolean,
            default: false,
        },
        arrow: {
            type: Boolean,
            default: false,
        },
    },

    data () {
        return {
            show: false,
            localButton: structuredClone(this.value),
            localPages: [],
            linkTypeOptions,
        }
    },

    computed: {
        disabled() {
            return false
        },

    },

    methods: {

        saveButton () {
            this.$emit('saved', this.localButton)
            this.resetModal()
        },

        resetModal (){
            this.localButton = structuredClone(this.value)
            this.show = false
        },

        async fetchPages () {
            try {
                const payload = {
                    params: {
                        // field: 'id,name',
                        append: 'paths',
                        selectOptions: true
                    }
                }
                const { data } = await PageTree.index(payload)
                this.localPages = data
            } catch (error) {
                console.error(error)
            }
        },

        checkForPages() {
            this.fetchPages()
        }
    },
}
</script>
