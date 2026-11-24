<template>
    <Transition
        :appear="disabled"
        :css="false"
        @enter="onEnter"
        @leave="onLeave"
    >
        <div
            v-show="open"
            ref="element"
        >
            <slot />
        </div>
    </Transition>
</template>

<script setup>
import { onBeforeUnmount } from 'vue'

const open = defineModel({
    type: Boolean,
})

open.value ??= false

const props = defineProps({
    disabled: {
        type: Boolean,
        default: false,
    },
    duration: {
        type: [Number, String, Object],
        default: 500,
    },
})

let observer
let animating = false

function observe(element) {
    observer?.disconnect()

    observer = new ResizeObserver(() => {
        if (!animating) return

        element.style.height = `${element.scrollHeight}px`
    })

    observer.observe(element)
}

function animate({ element, from, to, duration, done }) {
    animating = true

    element.style.height = `${from}px`
    element.style.overflow = 'hidden'

    void element.offsetHeight

    element.style.transition = `height ease ${duration}ms`
    element.style.height = `${to}px`

    const onEnd = (e) => {
        if (e.propertyName !== 'height') return

        element.removeEventListener('transitionend', onEnd)

        animating = false
        delete element.style.transition
        element.style.overflow = 'visible'
        element.style.height = 'auto'

        done()
    }

    element.addEventListener('transitionend', onEnd)
}

function onEnter(element, done) {
    if (props.disabled) return done()

    observe(element)

    animate({
        element,
        from: 0,
        to: element.scrollHeight,
        duration: props.duration.open ?? props.duration,
        done,
    })
}

function onLeave(element, done) {
    if (props.disabled) return done()

    observer?.disconnect()

    animate({
        element,
        from: element.scrollHeight,
        to: 0,
        duration: props.duration.close ?? props.duration,
        done,
    })
}

onBeforeUnmount(() => {
    observer?.disconnect()
})
</script>