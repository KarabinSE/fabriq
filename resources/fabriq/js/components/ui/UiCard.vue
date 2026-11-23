<template>
    <div
        class="mt-4 bg-white rounded card @container/card"
        :class="noShadow ? 'border border-gray-200' : 'shadow-md'"
    >
        <div
            v-if="hasHeaderSlot"
            :class="cn(
                'flex items-center leading-none transition-colors duration-500 border-b justify-between cursor-pointer',
                padding && 'px-4 md:px-6',
                collapsedActive ? 'border-gray-200' : 'border-transparent',
                cHeaderClasses,
            )"
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
                    class="block size-4 @xl/card:size-5 text-gray-800 transition-[colors,rotate] duration-300"
                    :class="open ? '-rotate-180' : 'rotate-0'"
                />
            </div>
        </div>
        <div
            v-if="! collapsible"
            :class="cn(padding && 'p-6')"
        >
            <slot />
        </div>
        <SlideUpDown
            v-else
            v-model="open"
            duration="500"
        >
            <div :class="cn(padding && 'p-6')">
                <slot />
            </div>
        </SlideUpDown>
    </div>
</template>

<script setup>
import SlideUpDown from '@fabriq/plugins/SlideUpDown.vue'
import { useUiStore } from '@fabriq/stores'
import { useEventBus } from '@fabriq/services/eventBus'
import { cn } from '@fabriq/helpers/utils'
import { onMounted, onUnmounted, ref, useSlots } from 'vue'
import { computed } from 'vue'


const uiStore = useUiStore()
const eventBus = useEventBus()
const slots = useSlots()

const emit = defineEmits(['before-open', 'before-close'])

defineOptions({
    name: 'UiCard',
})
const props = defineProps({
    padding: {
        type: Boolean,
        default: true,
    },
    collapsible: {
        type: Boolean,
        default: false,
    },
    headerClasses: {
        type: [String, Array],
        default: null,
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
        default: null,
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
        default: null,
    },
})

const open = ref(false)
const delayOpen = ref(false)
const animationDuration = ref(500)

const hasHeaderSlot = computed(() => !!slots.header)
const collapsedActive = computed(() => props.collapsible && open.value)
const cHeaderClasses = computed(() => props.headerClasses || 'py-3 md:py-4')

onUnmounted(() => {
    eventBus.off('open-all-cards', openCollapsible)
    eventBus.off('close-all-cards', closeCollapsible)
    eventBus.off('open-synced-groups', matchGroupAndOpen)
    eventBus.off('close-synced-groups', matchGroupAndClose)
})
onMounted(() => {
    if (!props.isChild) {
        eventBus.on('open-all-cards', openCollapsible)
        eventBus.on('close-all-cards', closeCollapsible)
    }

    if (props.syncGroups) {
        eventBus.on('open-synced-groups', matchGroupAndOpen)
        eventBus.on('close-synced-groups', matchGroupAndClose)
    }

    if (props.collapsible && props.openByDefault) {
        open.value = true
    }

    if (props.identifier && uiStore.openCards.includes(props.identifier)) {
        animationDuration.value = 0
        open.value = true
        setTimeout(() => {
            animationDuration.value = 500
        }, 100)
    }
})

function matchGroupAndOpen (parameters) {
    if (props.group === parameters) {
        openCollapsible(false)
    }
}

function matchGroupAndClose (parameters) {
    if (props.group === parameters) {
        closeCollapsible(false)
    }
}

function openCollapsible (shouldEmit = true) {
    emit('before-open')

    if (props.syncGroups && shouldEmit) {
        eventBus.emit('open-synced-groups', props.group)
    }

    open.value = true
}

function closeCollapsible (shouldEmit = true) {
    emit('before-close')

    if (props.syncGroups && shouldEmit) {
        eventBus.emit('close-synced-groups', props.group)
    }

    open.value = false
}

function toggleCollapsible () {
    if (props.identifier) {
        uiStore.toggleOpenCard(props.identifier)
    }

    open.value
        ? closeCollapsible()
        : openCollapsible()
}
</script>
