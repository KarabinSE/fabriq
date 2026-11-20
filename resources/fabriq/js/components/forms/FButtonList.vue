<template>
    <div>
        <div class="flex justify-between mt-2 mb-3 text-sm font-semibold">
            <div class="text-xl font-light">
                <slot>Knappar</slot>
            </div>
            <button
                :disabled="addLocked"
                :class="{'cursor-not-allowed text-neutral-400': addLocked}"
                class="text-sm font-semibold focus:outline-none"
                type="button"
                @click="addButton"
            >
                <span
                    v-if="maxItems && maxItems <= model.length"
                    class="mr-4 text-xs italic font-normal text-neutral-400"
                >
                    Du har nått det maximala antalet knappar
                </span>
                <div
                    v-else
                    class="flex gap-2 items-center"
                >
                    <PlusIcon class="size-5" />Lägg till knapp
                </div>
            </button>
        </div>

        <UiDashedBox
            v-if="noButtons"
            size="min-h-24"
        >
            <template #header>
                <div class="text-base">
                    Ingen knapp har lagts till ännu
                </div>
            </template>
            <template #link>
                <div class="flex justify-center">
                    <button
                        class="flex gap-2 items-center text-sm font-semibold focus:outline-none"
                        type="button"
                        @click="addButton"
                    >
                        <PlusIcon class="size-5" />Lägg till knapp
                    </button>
                </div>
            </template>
        </UiDashedBox>

        <div
            v-for="(button, index) in buttons"
            :key="index"
            class="flex mb-3 space-x-6"
        >
            <FButtonItem
                v-model="buttons[index]"
                class="flex-1 col-span-12"
                :placeholder="placeholder"
                :pages="pages"
                :color="color"
                :arrow="arrow"
                can-delete
                @delete="spliceButton(index)"
            />

            <div
                v-if="mergedOptions.newTab"
                class="col-span-2"
            >
                <FLabel>
                    <span class="flex items-center">
                        Öppna i ny flik? <ExternalLinkIcon class="size-4 ml-2" />
                    </span>
                </FLabel>
                <div class="flex items-center mt-3">
                    <FSwitch v-model="button.newTab" />
                    <div
                        class="ml-2 text-sm"
                        v-text="button.newTab ? 'Ja' : 'Nej'"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import PageTree from '@fabriq/models/PageTree.js'
import { onBeforeMount, ref, watch, watchEffect } from 'vue'
import { computed } from 'vue'
import { reactive } from 'vue'
import { Button } from '@fabriq/models/Button'

defineOptions({name: 'FButtonList'})

const buttons = defineModel({
    type: Array,
    default: () => [],
})

const emit = defineEmits(['update:model-value'])
const props = defineProps({
    options: {
        type: Object,
        default: () => ({})
    },
    maxItems: {
        type: Number,
        default: null
    },
    placeholder: {
        type: String,
        default: null,
    },
    color: {
        type: Boolean,
        default: false,
    },
    arrow: {
        type: Boolean,
        default: false,
    }
})

watchEffect(() => {
    if(buttons.value === undefined) {
        emit('update:model-value', [])
    }
})

const pages = ref([])
const defaultOptions = reactive({
    newTab: false
})

const noButtons = computed(() => buttons.value.length === 0)
const addLocked = computed(() => props.maxItems && props.maxItems <= buttons.value.length)
const mergedOptions = computed(() => ({
    ...defaultOptions,
    ...props.options,
}))

onBeforeMount(() => {
    fetchPages()
})

async function fetchPages () {
    try {
        const payload = {
            params: {
                append: 'paths',
                selectOptions: true
            }
        }
        const { data } = await PageTree.index(payload)
        pages.value = data
    } catch (error) {
        console.error(error)
    }
}

function addButton () {
    buttons.value.push(new Button)
}

function spliceButton(index) {
    buttons.value.splice(index, 1)
}
</script>
