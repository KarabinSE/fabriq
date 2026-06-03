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
            <ArrowRightLongIcon
                v-if="option.value !== 'none'"
                class="w-5 h-5"
                :class="[
                    option.value === 'down' && 'rotate-90'
                ]"
            />
            <span v-else>
                {{ option.label }}
            </span>
        </template>
        <template #prefix="option">
            <div class="w-8">
                <ArrowRightLongIcon
                    v-if="option.value !== 'none'"
                    class="w-5 h-5"
                    :class="[
                        option.value === 'down' && 'rotate-90'
                    ]"
                />
            </div>
        </template>
    </FSelect>
</template>
<script>
import ArrowRightLongIcon from '@/icons/ArrowRightLongIcon.vue'
export default {
    name: 'FArrowPicker',
    components: {
        ArrowRightLongIcon,
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
            buttonArrows: [
                { label: 'Ingen', value: 'none' },
                { label: 'Ner', value: 'down' },
                { label: 'Höger', value: 'right' },
            ],
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
            return this.buttonArrows
        }
    }
}
</script>
