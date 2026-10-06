<template>
    <div
        ref="container"
        class="flex flex-wrap gap-4 items-end justify-between mt-5"
    >
        <div
            class="flex items-baseline"
        >
            <div
                class="mr-4 font-light text-3xl md:text-4xl"
            >
                <slot />
            </div>
            <div class="text-sm font-semibold text-gray-400">
                <slot name="subtitle" />
            </div>
        </div>

        <div>
            <TransitionGroup
                v-if="hasToolsSlot"
                name="fade"
            >
                <div
                    v-if="showFixedTools"
                    key="nonfixedTools"
                    class="fixed z-50 p-2.5 bg-white rounded shadow-md left-4 right-4 md:left-auto md:right-8 div-c fixed-tools top-20 lg:top-5"
                >
                    <slot name="tools" />
                </div>
            </TransitionGroup>
            <div
                key="fixedTools"
                class="ml-auto div-c w-auto"
                :class="showFixedTools && 'invisible'"
            >
                <slot name="tools" />
            </div>
        </div>
    </div>
</template>
<script>
export default {
    name: 'UiSectionHeader',
    data () {
        return {
            observer: null,
            showFixedTools: false
        }
    },
    computed: {
        hasItemSlot () {
            return !!this.$slots.item
        },
        hasToolsSlot () {
            return !!this.$slots.tools
        }
    },
    mounted () {
        this.observer.observe(this.$refs.container)
    },
    created () {
        this.observer = new IntersectionObserver(
            this.onElementObserved,
            {
                root: this.$refs.container,
                threshold: 1.0
            }
        )
    },
    beforeDestroy () {
        this.observer.disconnect()
    },
    methods: {
        onElementObserved (entries) {
            entries.forEach(({ target, isIntersecting }) => {
                this.showFixedTools = !isIntersecting
            })
        }
    }
}
</script>
<style>
.div-b {
  text-overflow: ellipsis;
  white-space: nowrap;
  overflow: hidden;
  margin-right: 5px;
  width: 0;
  flex-grow: 1;
  max-width: -moz-max-content;
  max-width: -webkit-max-content;
  max-width: max-content;
}
.div-c {
  white-space: nowrap;
}
</style>
