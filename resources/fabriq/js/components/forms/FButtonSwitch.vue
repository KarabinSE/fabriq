<template>
    <button
        v-if="! computedValue"
        type="button"
        class="focus:outline-none w-7"
        @click.stop="computedValue = true"
    >
        <slot name="on">
            <EyeIcon
                v-tooltip="'Dölj'"
                class="inline-flex h-5 leading-none transition-colors focus:outline-none"
                thin
            />
        </slot>
    </button>
    <button
        v-else
        type="button"
        class="w-7 focus:outline-none"
        @click.stop="computedValue = false"
    >
        <slot name="off">
            <EyeSlashIcon
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
        value: {
            type: [Number, Boolean, String],
            default: false,
        },
    },

    data () {
        return {
            newValue: this.value,
        }
    },

    computed: {
        computedValue: {
            get () {
                return this.newValue
            },

            set (value) {
                this.newValue = value
                this.$emit('input', value)
            },
        },
    },

    watch: {
        /**
        * When v-model change, set internal value.
        */
        value (value) {
            this.newValue = value
        },
    },
}
</script>
