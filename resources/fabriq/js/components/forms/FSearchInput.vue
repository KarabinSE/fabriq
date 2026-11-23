<template>
    <input
        type="search"
        :value="modelValue"
        @input="updateValue($event.target.modelValue)"
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
            default: '',
        },
    },

    emits: ['input', 'perform-search', 'clear-search'],

    data () {
        return {
            search: '',
        }
    },

    watch: {
        search: debounce(function (newValue) {
            this.$emit('perform-search')
        }, 250),
    },

    methods: {
        clearValue () {
            this.$emit('clear-search')
            this.$emit('input', '')
        },

        updateValue (value) {
            console.log('updating')
            this.search = value
            this.$emit('input', value)
        },
    },

}
</script>
