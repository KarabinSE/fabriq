<template>
    <div class="space-y-1 w-full border p-2 border-gray-200 rounded">
        <div class="flex justify-between items-center gap-5">
            <div class="flex gap-4 items-center">
                <div
                    v-if="color"
                    class="rounded-full size-4 aspect-square"
                    :class="[
                        buttonColor ? buttonColor : 'border border-gray-300'
                    ]"
                />
                {{ model.text }}
                <span
                    v-if="!model.text"
                    class="italic text-gray-500"
                >&lt;Knapptext&gt;</span>

                <ArrowPresenter
                    v-if="arrow"
                    :arrow="buttonArrow"
                />
            </div>

            <div class="flex gap-3">
                <button
                    type="button"
                    @click="showModal"
                >
                    <PenToSquareIcon class="size-5 text-gray-500 hover:text-gray-700" />
                </button>
                <button
                    type="button"
                    class=""
                    @click="emit('delete')"
                >
                    <TrashIcon class="size-5 text-gray-500 hover:text-red-800" />
                </button>
            </div>
        </div>

        <div class="flex items-baseline text-sm text-gray-500">
            <div class="text-xs pr-3 font-semibold border-r border-gray-300 text-gray-400 leading-tight">
                {{ buttonType }}
            </div>
            <div class="pl-3">
                {{ buttonTarget }}
                <span
                    v-if="!buttonTarget"
                    class="text-italic text-xs"
                >
                    ej angivet
                </span>
            </div>
        </div>

        <ButtonModal
            :id="modalId"
            v-model="model"
            :arrow="arrow"
            :color="color"
        />
    </div>
</template>

<script>
export const linkTypeOptions = [
    {
        label: 'Intern',
        value: 'internal',
    },
    {
        label: 'Extern',
        value: 'external',
    },
    {
        label: 'Fil',
        value: 'file',
    },
]
</script>

<script setup>
import PageTree from '@fabriq/models/PageTree.js'
import { colors as pallette } from '@fabriq/components/forms/FColorPicker.vue'
import { buttonArrows } from '@fabriq/components/forms/FArrowPicker.vue'
import PenToSquareIcon from '@fabriq/icons/PenToSquareIcon.vue'
import ArrowPresenter from '@fabriq/components/forms/ArrowPresenter.vue'
import ButtonModal from '@fabriq/components/modals/ButtonModal.vue'
import { Button } from '@fabriq/models/Button'
import { useVfm } from 'vue-final-modal'
import { onMounted, ref, computed } from 'vue'

const vfm = useVfm()

defineOptions({
    name: 'FButtonItem',
})
const emit = defineEmits(['delete'])

const model = defineModel({
    type: Object,
    default: () => new Button(),
})

const props = defineProps({
    pages: {
        type: Array,
        default: () => [],
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
    columnLayout: {
        type: Boolean,
        default: false,
    },
    placeholder: {
        type: String,
        default: '',
    },
    canDelete: {
        type: Boolean,
        default: false,
    },
})

const modalId = ref(`button-modal-${Math.random().toString(20).substring(2, 6)}`)
const localPages = ref(props.pages)
const colors = ref(pallette['buttonColors'])
const arrows = ref(buttonArrows)

const buttonType = computed(() => {
    if(!model.value) return ''

    return linkTypeOptions.find(opt => opt.value === model.value.linkType)?.label ?? ''
})
const buttonTarget = computed(() => {
    if(!model.value) return ''

    return {
        'internal': localPages.value.find(page => page.id === model.value.page_id)?.name ?? '',
        'external': model.value.url ?? '',
        'file': model.value.file?.file_name ?? '',
    }[model.value.linkType] ?? ''
})
const buttonColor = computed(() => {
    if(!model.value || !colors.value || model.value.linkType === 'file') return ''

    return colors.value.find(color => color.value === model.value.color)?.color ?? ''
})
const buttonArrow = computed(() => {
    if(!model.value || !arrows.value) return

    return arrows.value.find(arrow => arrow.value === model.value.arrow)
})

onMounted(() => {
    if (localPages.value.length === 0) {
        fetchPages()
    }
})

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

function checkForPages () {
    fetchPages()
}

function showModal () {
    vfm.open(modalId.value)
}

</script>
