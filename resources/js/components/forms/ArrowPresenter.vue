<template>
    <ArrowRightLongIcon
        v-if="arrow && arrow.value !== 'none'"
        class="size-5"
        :class="[
            arrow.value === 'down' && 'rotate-90'
        ]"
    />
</template>

<script>
import ArrowRightLongIcon from '@/icons/ArrowRightLongIcon.vue'

export default {
    components: { ArrowRightLongIcon },
    props: {
        arrow: {
            type: Object,
            default: null
        },
    }
}
</script>