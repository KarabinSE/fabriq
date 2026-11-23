<template>
    <div>
        <ul
            v-show="showTabs && ! hideTabs"
            class="flex -mb-px space-x-5 border-b border-gray-200"
        >
            <li
                v-for="(tab, index) in tabs"
                :key="tab.title"
                class="px-1 py-4 text-sm font-medium transition-colors duration-200 border-b-2 cursor-pointer whitespace-nowrap min-w-14"
                :class="[
                    index === selectedIndex
                        ? 'border-royal-400 text-gray-800'
                        : 'border-transparent text-gray-400 hover:text-gray-600 hover:border-gray-300'
                ]"
                @click="selectTab(index)"
            >
                <div class="flex items-center justify-center">
                    <CircleExclamationIcon
                        v-if="tab.hasError"
                        class="size-5 mr-2 text-red-500"
                    />
                    {{ tab.title }}
                </div>
            </li>
        </ul>
        <slot />
    </div>
</template>

<script>
const tabsContextKey = 'fabriq-tabs-context'

import { useEventBus } from '@fabriq/services/eventBus'

export default {
    name: 'FTabs',
    provide () {
        return {
            [tabsContextKey]: {
                registerTab: this.registerTab,
                unregisterTab: this.unregisterTab,
            },
        }
    },

    props: {
        showWhenSingle: {
            type: Boolean,
            default: false,
        },

        hideTabs: {
            type: Boolean,
            default: false,
        },

        identifier: {
            type: String,
            default: () => Math.random().toString(20).substr(2, 6),
        },
    },

    emits: ['change'],
    setup() {
        const eventBus = useEventBus()

        return {
            eventBus,
        }
    },

    data () {
        return {
            selectedIndex: 0, // the index of the selected tab,
            tabs: [],
        }
    },

    computed: {
        showTabs () {
            if (this.tabs.length === 1) {
                if (this.showWhenSingle) {
                    return true
                }

                return false
            }

            return true
        },
    },

    mounted () {
        this.selectTab(0)
        this.eventBus.on('set-active-tab', this.handleActiveTabEvent)
    },

    unmounted () {
        this.eventBus.off('set-active-tab', this.handleActiveTabEvent)
    },

    methods: {
        registerTab (tab) {
            const registeredTab = {
                get title () {
                    return tab.title
                },

                get hasError () {
                    return tab.hasError
                },

                get valueKey () {
                    return tab.valueKey
                },

                setActive (isActive) {
                    tab.isActive = isActive
                },
            }

            this.tabs.push(registeredTab)

            return registeredTab
        },

        unregisterTab (tab) {
            const index = this.tabs.indexOf(tab)

            if (index !== -1) {
                this.tabs.splice(index, 1)
            }
        },

        handleActiveTabEvent (payload) {
            if (payload.identifier !== this.identifier) {
                return
            }

            this.selectTab(payload.index)
        },

        selectTab (index_) {
            if (!this.tabs[index_]) {
                return
            }

            this.selectedIndex = index_

            this.$emit('change', this.tabs[index_].valueKey)

            // loop over all the tabs
            this.tabs.forEach((tab, index) => {
                tab.setActive(index === index_)
            })
        },
    },
}
</script>
