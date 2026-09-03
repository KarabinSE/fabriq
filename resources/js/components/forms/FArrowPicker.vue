<template>
    <FSelect
        v-model="arrow"
        :label="label"
        :name="name"
        value-key="value"
        option-label="label"
        :clearable="false"
        :options="getArrowOptions()"
        :disabled="disabled"
        default-value="none"
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
import ArrowPresenter from '@/components/forms/ArrowPresenter.vue'

export const buttonArrows = [
    { label: 'Ingen', value: 'none' },
    { label: 'Ner', value: 'down' },
    { label: 'Höger', value: 'right' },
]

export default {
    name: 'FArrowPicker',
    components: {
        ArrowPresenter,
    },
    props: {
        value: {
            type: String,
            default: ''
        },
        label: {
            type: String,
            default: 'Pil'
        },
        name: {
            type: String,
            default: ''
        },
        disabled: {
            type: Boolean,
            default: false
        }
    },
    data () {
        return {
            arrow: 'none',
        }
    },
    mounted () {
        if (!this.value) {
            this.$emit('input', this.arrow)
        } else {
            this.arrow = this.value
        }
    },
    methods: {
        emitValue (value) {
            this.arrow = value
            this.$emit('input', value)
        },
        getArrowOptions() {
            return buttonArrows
        }
    }
}
</script>
