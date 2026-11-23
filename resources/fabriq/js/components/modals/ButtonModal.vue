<template>
    <FModal
        v-model="show"
        :name="id"
        :click-to-close="true"
        :esc-to-close="model.linkType !== 'file'"
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
                    v-model="model.text"
                    :disabled="disabled"
                    label="Knapptext"
                />
                <FInput
                    v-model="model.linkType"
                    input-type="radio"
                    class="col-span-4 xl:col-span-3"
                    :disabled="disabled"
                    name="linkType"
                    :options="linkTypeOptions"
                    label="Länktyp"
                />
                <FInput
                    v-if="model.linkType === 'external'"
                    v-model="model.url"
                    class="h-18"
                    :disabled="disabled"
                    label="Länk"
                    placeholder="https://exempel.se eller /min-sida"
                />
                <FSelect
                    v-if="model.linkType === 'internal'"
                    v-model="model.page_id"
                    class="h-18"
                    value-key="id"
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
                    v-if="model.linkType === 'file'"
                    v-model="model.file"
                    class="h-18"
                    :disabled="disabled"
                    label="Fil"
                />

                <template v-else>
                    <FColorPicker
                        v-if="color"
                        v-model="model.color"
                        class="w-full"
                        :disabled="disabled"
                    />
                    <FArrowPicker
                        v-if="arrow"
                        v-model="model.arrow"
                        class="w-full"
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

<script setup>
import FArrowPicker from '@fabriq/components/forms/FArrowPicker.vue'
import { linkTypeOptions } from '@fabriq/components/forms/FButtonItem.vue'
import FColorPicker from '@fabriq/components/forms/FColorPicker.vue'
import { Button } from '@fabriq/models/Button'
import PageTree from '@fabriq/models/PageTree'
import { ref } from 'vue'

defineOptions({
    name: 'ButtonModal',
})
const model = defineModel({
    type: Object,
    default: () => new Button(),
})

const emit = defineEmits(['saved'])
const props = defineProps({
    id: {
        type: String,
        required: true,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    color: {
        type: Boolean,
        default: false,
    },
    arrow: {
        type: Boolean,
        default: false,
    },
})

const show = ref(false)
const localPages = ref([])


function saveButton () {
    resetModal()
}

function resetModal (){
    show.value = false
}

async function fetchPages () {
    try {
        const payload = {
            params: {
                // field: 'id,name',
                append: 'paths',
                selectOptions: true,
            },
        }
        const { data } = await PageTree.index(payload)

        localPages.value = data
    } catch (error) {
        console.error(error)
    }
}

function checkForPages() {
    fetchPages()
}
</script>
