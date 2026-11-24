<template>
    <FSelect
        v-model="color"
        :label="label"
        :name="name"
        value-key="value"
        option-label="label"
        :clearable="false"
        :options="getColorOptions()"
        :disabled="disabled"
        class="w-44"
    >
        <template #fop="option">
            <div
                class="size-5 mr-4 border-gray-200 rounded-full aspect-square"
                :class="option.color"
            />
            <span>
                {{ option.label }}
            </span>
        </template>
        <template #prefix="option">
            <div>
                <div
                    class="size-5 mr-4 border border-gray-200 rounded-full"
                    :class="option.color"
                />
            </div>
        </template>
    </FSelect>
</template>

<script setup>
import { useFabriq } from '@fabriq/fabriq'


defineOptions({
    name: 'FColorPicker',
})

const  props = defineProps({
    value: {
        type: String,
        default: null,
    },
    label: {
        type: String,
        default: 'Färg',
    },
    collection: {
        type: String,
        default: 'button',
    },
    name: {
        type: String,
        default: '',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
})

const fabriq = useFabriq()

const color = defineModel({
    type: String,
})

color.value ??= 'primary'

function getColorOptions() {
    try {
        return fabriq.colors[props.collection]
    } catch (error) {
        console.error(`found no color pallette for ${props.collection}` )
        console.error(`found collections: ${Object.keys(fabriq.colors).join(', ')}`)
    }
}
</script>
