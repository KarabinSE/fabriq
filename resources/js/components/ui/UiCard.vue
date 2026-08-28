<template>
    <div
        class="mt-4 bg-white rounded card @container/card"
        :class="noShadow ? 'border border-gray-200' : 'shadow-md'"
    >
        <div
            v-if="hasHeaderSlot"
            :class="[
                padding && 'px-4 md:px-6',
                collapsible ? [
                    'cursor-pointer flex justify-between border-transparent',
                    collapsedActive ? 'border-gray-200' : 'border-transparent',
                    open ? 'bg-white' : 'rounded-b',
                ] : 'border-gray-200',
                cHeaderClasses,
            ]"
            class="flex items-center leading-none transition-colors duration-500 bg-white border-b border-gray-200 rounded-t justify-between"
            @click="toggleCollapsible"
        >
            <div class="text-xl font-light text-gray-700 w-full min-w-0">
                <slot name="header" />
            </div>
            <div
                v-if="collapsible"
                class="shrink-0 pl-4"
            >
                <AngleDownIcon
                    class="block size-5 @xl/card:size-6 text-gray-800 transition-[colors,rotate] duration-300"
                    :class="open ? '-rotate-180' : 'rotate-0'"
                />
            </div>
        </div>
        <div
            v-if="! collapsible"
            :class="{'p-6': padding }"
        >
            <slot />
        </div>
        <SlideUpDown
            v-else
            ref="monkey"
            :active="open"
            :duration="animationDuration"
            class="wobbly-accordion"
            @open-start="delayOpen = true"
            @close-end="delayOpen = false"
        >
            <div
                v-if="delayOpen"
                :class="{'p-6': padding }"
            >
                <slot />
            </div>
        </SlideUpDown>
    </div>
</template>

<script>
import { useUiStore } from '@/stores';

export default {
    name: 'UiCard',
    props: {
        padding: {
            type: Boolean,
            default: true,
        },

        collapsible: {
            type: Boolean,
            default: false,
        },

        headerClasses: {
            type: String,
            default: '',
        },

        noShadow: {
            type: Boolean,
            default: false,
        },

        isChild: {
            type: Boolean,
            default: false,
        },

        group: {
            type: String,
            default: '',
        },

        syncGroups: {
            type: Boolean,
            default: false,
        },

        openByDefault: {
            type: Boolean,
            default: false,
        },

        identifier: {
            type: String,
            default: '',
        },
    },
    setup () {
        const uiStore = useUiStore();

        return { uiStore }
    },

    data () {
        return {
            open: false,
            delayOpen: false,
            animationDuration: 500,
        }
    },

    computed: {
        hasHeaderSlot () {
            return !!this.$slots.header
        },

        collapsedActive () {
            return this.open && this.collapsible
        },

        cHeaderClasses () {
            if (this.headerClasses) {
                return this.headerClasses
            }

            return 'py-3 md:py-4'
        },

        openCards() {
            return this.uiStore.openCards;
        },
    },

    beforeDestroy () {
        this.$eventBus.off('open-all-cards', this.openCollapsible)
        this.$eventBus.off('close-all-cards', this.closeCollapsible)
        this.$eventBus.off('open-synced-groups', this.matchGroupAndOpen)
        this.$eventBus.off('close-synced-groups', this.matchGroupAndClose)
    },

    mounted () {
        if (!this.isChild) {
            this.$eventBus.on('open-all-cards', this.openCollapsible)
            this.$eventBus.on('close-all-cards', this.closeCollapsible)
            this.$eventBus.on('relayout-cards', this.relayoutCard)
        }

        if (this.syncGroups) {
            this.$eventBus.on('open-synced-groups', this.matchGroupAndOpen)
            this.$eventBus.on('close-synced-groups', this.matchGroupAndClose)
        }

        if (this.collapsible && this.openByDefault) {
            this.open = true
        }

        if (this.identifier && this.openCards.includes(this.identifier)) {
            this.animationDuration = 0
            this.open = true
            setTimeout(() => {
                this.animationDuration = 500
            }, 100);
        }
    },

    methods: {
        relayoutCard () {
            if (this.$refs.monkey) {
                this.animationDuration = 0
                this.$refs.monkey.layout()
                setTimeout(() => {
                    this.animationDuration = 500
                }, 100)
            }
        },

        matchGroupAndOpen (parameters) {
            if (this.group === parameters) {
                this.openCollapsible(false)
            }
        },

        matchGroupAndClose (parameters) {
            if (this.group === parameters) {
                this.closeCollapsible(false)
            }
        },

        openCollapsible (emit = true) {
            this.$emit('before-open')

            if (this.syncGroups) {
                if (emit) {
                    this.$eventBus.emit('open-synced-groups', this.group)
                }
            }

            this.open = true
        },

        closeCollapsible (emit = true) {
            this.$emit('before-close')

            if (this.syncGroups) {
                if (emit) {
                    this.$eventBus.emit('close-synced-groups', this.group)
                }
            }

            this.open = false
        },

        toggleCollapsible () {
            if (this.identifier) {
                this.uiStore.toggleOpenCard(this.identifier);
            }

            if (this.open) {
                this.closeCollapsible()

                return
            }

            this.openCollapsible()
        },
    },
}
</script>
