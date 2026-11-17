<template>
    <UiCard
        class="overflow-hidden rounded-xl"
        :padding="false"
        no-shadow
    >
        <div class="flex items-center justify-between gap-4 px-6 py-4">
            <h3 class="text-sm font-semibold text-gray-800">
                Besök per källa
            </h3>
            <select
                v-model.number="itemLimit"
                aria-label="Antal poster per kategori"
                class="rounded border border-gray-200 bg-white px-2 py-1 text-sm text-gray-700 focus:border-royal-500 focus:outline-none"
            >
                <option :value="5">
                    5 poster
                </option>
                <option :value="10">
                    10 poster
                </option>
                <option :value="15">
                    15 poster
                </option>
            </select>
        </div>

        <div class="grid grid-cols-1 gap-px bg-gray-200 md:grid-cols-2 lg:grid-cols-3">
            <section
                v-for="breakdown in breakdowns"
                :key="breakdown.title"
                class="min-w-0 bg-white px-6 py-5"
            >
                <h4 class="mb-3 text-sm font-semibold text-gray-800">
                    {{ breakdown.title }}
                </h4>

                <div
                    v-if="breakdown.items.length > 0"
                    class="space-y-3"
                >
                    <div
                        v-for="item in breakdown.items.slice(0, itemLimit)"
                        :key="item.name"
                        class="flex items-center gap-3 text-[13px] font-mono"
                    >
                        <span
                            class="min-w-0 flex-1 truncate text-gray-600"
                            :title="item.name"
                        >
                            {{ item.name }}
                        </span>
                        <span class="w-6 shrink-0 text-right tabular-nums text-gray-600">
                            {{ item.visits }}
                        </span>
                        <div
                            aria-hidden="true"
                            class="h-2 w-20 shrink-0 overflow-hidden rounded-sm bg-gray-200"
                        >
                            <div
                                class="h-full bg-royal-500"
                                :style="{ width: barWidth(item) }"
                            />
                        </div>
                    </div>
                </div>
                <p
                    v-else
                    class="text-sm text-gray-500"
                >
                    Ingen data
                </p>
            </section>
        </div>
    </UiCard>
</template>

<script>
export default {
    name: 'AnalyticsStats',

    props: {
        data: {
            type: Object,
            default: () => ({})
        }
    },

    data () {
        return {
            itemLimit: 15
        }
    },

    computed: {
        breakdowns () {
            const toRows = (items, labelKey = 'name') => (items || []).map(item => ({
                name: item[labelKey] ?? '—',
                visits: item.visits ?? 0
            }))

            return [
                {
                    title: 'Hänvisningar',
                    items: toRows(this.data.referers).map(item => ({
                        ...item,
                        name: item.name || 'Direkt'
                    }))
                },
                {
                    title: 'Sökvägar',
                    items: toRows(this.data.paths)
                },
                {
                    title: 'Länder',
                    items: toRows(this.data.countries, 'code')
                },
                {
                    title: 'Webbläsare',
                    items: toRows(this.data.browsers)
                },
                {
                    title: 'Operativsystem',
                    items: toRows(this.data.operatingSystems)
                },
                {
                    title: 'Enhetstyper',
                    items: toRows(this.data.devices)
                }
            ]

        }
    },

    methods: {
        barWidth (item) {
            const totalVisits = Number((this.data.total && this.data.total.visits) || 0)

            if (totalVisits <= 0) {
                return '0%'
            }

            return `${Math.min((item.visits / totalVisits) * 100, 100)}%`
        }
    }
}
</script>
