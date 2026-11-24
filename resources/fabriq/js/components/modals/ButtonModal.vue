<template>
    <FModal
        name="button-edit-modal"
        :click-to-close="true"
        :esc-to-close="localButton.linkType !== 'file'"
        overflow="overflow-y-visible"
        width="max-w-xl"
        @before-open="fetchPages"
    >
        <template #title>
            <span
                class="text-gray-700 fd"
                v-text="'Redigera knapp'"
            />
        </template>

        <template #actions="{ close }">
            <div class="flex justify-end space-x-4">
                <button
                    class="px-8 py-2.5 leading-none fabriq-btn btn-link"
                    @click="close"
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
                    value-key="value"
                    :options="linkTypeOptions"
                    label="Länktyp"
                />
                <FInput
                    v-if="localButton.linkType === 'external'"
                    v-model="localButton.url"
                    :disabled="disabled"
                    label="Länk"
                    placeholder="https://exempel.se eller /min-sida"
                />
                <FSelect
                    v-if="localButton.linkType === 'internal'"
                    v-model="localButton.page_id"
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
                    v-if="localButton.linkType === 'file'"
                    v-model="localButton.file"
                    :disabled="disabled"
                    label="Fil"
                />

                <template v-else>
                    <FColorPicker
                        v-if="color"
                        v-model="localButton.color"
                        class="w-full"
                        :disabled="disabled"
                    />
                    <FArrowPicker
                        v-if="arrow"
                        v-model="localButton.arrow"
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
import { toRaw } from 'vue'
import { ref } from 'vue'

defineOptions({
    name: 'ButtonModal',
})

const emit = defineEmits(['save', 'closeModal'])
const props = defineProps({
    button: {
        type: Object,
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

const localButton = ref(structuredClone(toRaw(props.button) ?? new Button))
const localPages = ref([])

function saveButton () {
    emit('save', localButton.value)
    emit('closeModal')
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
