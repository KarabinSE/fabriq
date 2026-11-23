<template>
    <div>
        <div
            v-if="!config.web_analytics"
            class="grid grow grid-cols-4 gap-8 mt-16"
        >
            <StatsBox
                :delay="0"
                :number="pageCount"
                title="Antal sidor"
            />
            <StatsBox
                :delay="0"
                :number="articleCount"
                title="Antal nyheter"
            />
            <StatsBox
                :delay="0"
                :number="imageCount"
                title="Antal bilder"
            />
            <StatsBox
                :delay="0"
                :number="fileCount"
                title="Antal filer"
            />
        </div>

        <div
            v-if="config.web_analytics"
            class="flex mt-5 flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <h2 class="font-light text-3xl md:text-4xl">
                Statistik
            </h2>
            <div class="flex items-center gap-3">
                <SpinIcon
                    v-if="analyticsLoading"
                    class="w-6 h-6 animate-spin text-royal-500"
                />
                <FSelect
                    name="analytics-period"
                    :value="analyticsPeriod"
                    :options="analyticsPeriods"
                    :disabled="analyticsLoading"
                    :clearable="false"
                    class="w-full sm:w-64"
                    @input="changeAnalyticsPeriod"
                />
            </div>
        </div>

        <template v-if="chartData && config.web_analytics">
            <UiCard
                class="rounded-xl"
                no-shadow
            >
                <div class="flex gap-4">
                    <HelpText>Totalt antal besökare: {{ chartData.total.visits }}</HelpText>
                    <HelpText>Totalt antal visningar: {{ chartData.total.pageViews }}</HelpText>
                </div>
                <LineChart
                    v-if="chartData"
                    :options="{}"
                    :data="chartData.daily"
                />
            </UiCard>
            <AnalyticsStats
                v-if="chartData"
                :data="chartData"
            />
        </template>
        <UiCard
            v-if="config.search_console_analytics"
            class="rounded-xl"
            :padding="false"
            no-shadow
        >
            <h3 class="px-6 py-5 text-xl font-light text-gray-800">
                Sökfrågor som leder till din webbplats
            </h3>
            <div
                v-if="searchConsoleLoading"
                class="flex justify-center border-t border-gray-200 py-8"
            >
                <SpinIcon class="w-6 h-6 animate-spin text-royal-500" />
            </div>
            <FTable
                v-else
                :columns="searchConsoleColumns"
                :rows="searchTermsRows"
                :options="{ shadow: false, emptyText: 'Inga sökfrågor att visa' }"
                :paginated="false"
            />
        </UiCard>
    </div>
</template>

<script>
import StatsBox from '@fabriq/dashboard/StatsBox.vue'
import Article from '@fabriq/models/Article.js'
import File from '@fabriq/models/File.js'
import Image from '@fabriq/models/Image.js'
import Page from '@fabriq/models/Page.js'
import LineChart from './analytics/LineChart.vue'
import axios from 'axios'
import AnalyticsStats from './analytics/AnalyticsStats.vue'
import { useConfigStore } from './stores'
import { route } from '@/generated/helpers/route'

export default {
    name: 'HomeIndex',
    components: {
        StatsBox,
        LineChart,
        AnalyticsStats,
    },

    setup () {
        const configStore = useConfigStore()

        return {
            configStore,
        }
    },

    data () {
        return {
            pageCount: 0,
            articleCount: 0,
            imageCount: 0,
            fileCount: 0,
            chartData: null,
            analyticsPeriod: '7d',
            analyticsPeriods: [
                {
                    label: 'Senaste 7 dagarna',
                    value: '7d',
                },
                {
                    label: 'Senaste 30 dagarna',
                    value: '30d',
                },
                {
                    label: 'Senaste 90 dagarna',
                    value: '3m',
                },
            ],

            analyticsLoading: false,
            searchConsoleData: null,
            searchConsoleLoading: true,
            searchConsoleColumns: [
                {
                    key: 'query',
                    title: 'Vanligaste frågorna',
                },
                {
                    key: 'clicks',
                    title: 'Klick',
                    thClasses: 'text-right',
                    tdClasses: 'text-right',
                },
                {
                    key: 'impressions',
                    title: 'Exponeringar',
                    thClasses: 'text-right',
                    tdClasses: 'text-right',
                },
            ],
        }
    },

    computed: {
        config() {
            return this.configStore.config
        },

        searchTermsRows () {
            return (this.searchConsoleData?.rows ?? []).map(row => ({
                query: row.keys?.[0] ?? '',
                clicks: row.clicks,
                impressions: row.impressions,
            }))
        },
    },

    activated () {
        this.fetchPageCount()
        this.fetchArticleCount()
        this.fetchFileCount()
        this.fetchImageCount()
        this.fetchAnalytics()
        this.fetchSearchConsole()
    },

    methods: {
        async fetchPageCount () {
            const { data } = await Page.count()

            this.pageCount = data.count
        },

        async fetchArticleCount () {
            const { data } = await Article.count()

            this.articleCount = data.count
        },

        async fetchImageCount () {
            const { data } = await Image.count()

            this.imageCount = data.count
        },

        async fetchFileCount () {
            const { data } = await File.count()

            this.fileCount = data.count
        },

        async changeAnalyticsPeriod (period) {
            this.analyticsPeriod = period
            await this.fetchAnalytics()
            await this.fetchSearchConsole()
        },

        async fetchAnalytics () {
            this.analyticsLoading = true

            try {
                const { data } = await axios.get(route('analytics.web-analytics'), {
                    params: {
                        period: this.analyticsPeriod,
                    },
                })

                this.chartData = data
            } finally {
                this.analyticsLoading = false
            }
        },

        async fetchSearchConsole () {
            this.searchConsoleLoading = true

            try {
                const { data } = await axios.get(route('analytics.search-console'), {
                    params: {
                        period: this.analyticsPeriod,
                    },
                })

                this.searchConsoleData = data
            } finally {
                this.searchConsoleLoading = false
            }
        },
    },
}
</script>
