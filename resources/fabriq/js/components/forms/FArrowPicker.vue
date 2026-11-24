<template>
    <FSelect
        v-model="arrow"
        :label="label"
        :name="name"
        value-key="value"
        option-label="label"
        :clearable="false"
        :options="buttonArrows"
        :disabled="disabled"
        class="w-24"
        @input="emitValue"
    >
        <template #fop="option">
            <ArrowPresenter :arrow="option" />
            <span v-if="option.value === 'none'">
                {{ option.label }}
            </span>
        </template>
        <template #prefix="option">
            <div class="w-8">
                <ArrowPresenter :arrow="option" />
            </div>
        </template>
    </FSelect>
</template>

<script>
export const buttonArrows = [
    {
        label: 'Ingen',
        value: 'none',
    },
    {
        label: 'Ner',
        value: 'down',
    },
    {
        label: 'Höger',
        value: 'right',
    },
]
</script>

<script setup>
import ArrowPresenter from '@fabriq/components/forms/ArrowPresenter.vue'

const props = defineProps({
    value: {
        type: String,
        default: '',
    },
    label: {
        type: String,
        default: 'Pil',
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

const arrow = defineModel({
    type: String,
})

arrow.value ??= 'none'
</script>
