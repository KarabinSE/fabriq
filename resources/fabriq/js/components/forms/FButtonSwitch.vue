<template>
    <button
        type="button"
        class="w-7 focus:outline-none"
        @click.stop="computedValue = !computedValue"
    >
        <slot name="on">
            <EyeIcon
                v-if="!computedValue"
                v-tooltip="'Dölj'"
                class="inline-flex h-5 leading-none transition-colors focus:outline-none"
                thin
            />
        </slot>
        <slot name="off">
            <EyeSlashIcon
                v-if="computedValue"
                v-tooltip="'Visa'"
                class="inline-flex h-5 leading-none transition-colors focus:outline-none"
                thin
            />
        </slot>
    </button>
</template>

<script>
export default {
    name: 'FButtonSwitch',

    props: {
        modelValue: {
            type: [Number, Boolean, String],
            default: false,
        },
    },

    emits: ['update:model-value'],

    computed: {
        computedValue: {
            get () { return this.modelValue },
            set (value) { this.$emit('update:model-value', value) },
        },
    },
}
</script>
