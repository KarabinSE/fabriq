<template>
    <UiStatsCard>
        <template #title>
            {{ title }}
        </template>
        <div class="text-4xl font-black text-center" />
        <template #value>
            {{ number }}
        </template>
    </UiStatsCard>
</template>
<script>
export default {
    name: 'StatsBox',
    props: {
        number: {
            type: Number,
            default: 0
        },
        delay: {
            type: Number,
            default: 1
        },
        title: {
            type: String,
            default: ''
        }
    },
}
</script>
