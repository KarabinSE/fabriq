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

<script>
export const colors = {
    buttonColors: [
        { label: 'Primär', color: 'bg-btn-primary border-btn-primary', value: 'primary' },
        { label: 'Primär kontur', color: 'border-btn-primary', value: 'primary-outline' },
        { label: 'Sekundär', color: 'bg-btn-secondary border-btn-secondary', value: 'secondary' },
        { label: 'Sekundär kontur', color: 'border-btn-secondary', value: 'secondary-outline' },
        { label: 'Tertiär', color: 'bg-btn-tertiary border-btn-tertiary', value: 'tertiary' },
        { label: 'Tertiär kontur', color: 'border-btn-tertiary', value: 'tertiary-outline' },
    ],
    backgroundColors: [
        { label: 'Primär', color: 'bg-primary', value: 'primary' },
        { label: 'Sekundär', color: 'bg-secondary', value: 'secondary' },
        { label: 'Tertiär', color: 'bg-tertiary', value: 'tertiary' },
    ]
}
</script>

<script setup>

defineOptions({name: 'FColorPicker'})

const  props = defineProps({
    value: {
        type: String,
        default: null
    },
    label: {
        type: String,
        default: 'Färg'
    },
    collection: {
        type: String,
        default: 'buttons'
    },
    name: {
        type: String,
        default: ''
    },
    disabled: {
        type: Boolean,
        default: false
    }
})

const color = defineModel({
    type: String,
    default: 'primary'
})

function getColorOptions() {
    if(!['buttons', 'backgrounds'].includes(props.collection)) {
        console.error(`expected buttons|backgrounds got:${props.collection}`)
    }

    if(props.collection === 'backgrounds'){
        return colors.backgroundColors
    }

    return colors.buttonColors
}
</script>
