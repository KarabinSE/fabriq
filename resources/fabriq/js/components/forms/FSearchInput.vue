<template>
    <input
        v-model="localValue"
        type="search"
        @keydown.esc="clearValue"
    >
</template>

<script>
import { debounce } from '@fabriq/helpers/debounce.js'

export default {
    name: 'FSearchInput',

    props: {
        modelValue: {
            type: String,
            required: true,
        },
    },

    emits: ['update:model-value', 'perform-search', 'clear-search'],

    computed: {
        localValue: {
            get() {
                return this.modelValue
            },

            set(value) {
                this.$emit('update:model-value', value)
            },
        },
    },

    watch: {
        localValue: debounce(function (newValue) {
            this.$emit('perform-search')
        }, 250),
    },

    methods: {
        clearValue () {
            this.$emit('clear-search')
            this.localValue = ''
        },
    },

}
</script>
