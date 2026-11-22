<template>
    <div
        v-show="isActive"
        class="py-6"
    >
        <slot />
    </div>
</template>

<script>
export default {
    name: 'FTab',
    inject: {
        tabsContext: {
            from: 'fabriq-tabs-context',
            default: null,
        },
    },

    props: {
        title: {
            required: true,
            type: String,
            default: 'Tab',
        },

        valueKey: {
            type: String,
            default: '',
        },

        hasError: {
            type: Boolean,
            default: false,
        },
    },

    data () {
        return {
            isActive: false,
            registeredTab: null,
        }
    },

    mounted () {
        if (this.tabsContext) {
            this.registeredTab = this.tabsContext.registerTab(this)
        }
    },

    unmounted () {
        if (this.tabsContext && this.registeredTab) {
            this.tabsContext.unregisterTab(this.registeredTab)
        }
    },
}
</script>
