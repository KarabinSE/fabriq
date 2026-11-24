<template>
    <div class="flex">
        <input
            :id="'chk' + _uid"
            v-model="computedValue"
            :name="'chk' + _uid"
            type="checkbox"
            :disabled="disabled"
            :value="checkboxValue"
            class="size-5 fabriq-checkbox form-checkbox focus:outline-none focus:ring-offset-3 focus:ring-1 focus:ring-royal-300"
        >
        <label
            :for="'chk' + _uid"
            class="block ml-3 text-sm font-medium text-gray-700"
        >
            <slot />
        </label>
    </div>
</template>

<script>
export default {
    name: 'FCheckbox',

    props: {
        label: {
            type: String,
            default: '',
        },

        modelValue: {
            type: [String, Array, Object, Boolean, Number],
            default: '',
        },

        checkboxValue: {
            type: [String, Array, Object, Boolean, Number],
            default: '',
        },

        disabled: {
            type: Boolean,
            default: false,
        },

    },

    emits: ['update:model-value'],

    computed: {
        computedValue: {
            get() {
                return this.modelValue
            },

            set(value) {
                this.$emit('update:model-value', value)
            },
        },
    },
}
</script>

<style>
</style>
