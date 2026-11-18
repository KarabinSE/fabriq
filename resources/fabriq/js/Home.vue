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

        <template v-if="chartData && config.analytics">
            <UiCard class="rounded-xl">
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
        StatsBox, LineChart, AnalyticsStats
    },
    setup () {
        const configStore = useConfigStore()

        return { configStore }
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
                { label: 'Senaste 7 dagarna', value: '7d' },
                { label: 'Senaste 30 dagarna', value: '30d' },
                { label: 'Senaste 90 dagarna', value: '3m' }
            ],
            analyticsLoading: false
        }
    },
    computed: {
        config() {
            return this.configStore.config;
        },
    },
    activated () {
        this.fetchPageCount()
        this.fetchArticleCount()
        this.fetchFileCount()
        this.fetchImageCount()
        this.fetchAnalytics()
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
        },
        async fetchAnalytics () {
            this.analyticsLoading = true

            try {
                const { data } = await axios.get(route('analytics.web-analytics'), {
                    params: {
                        period: this.analyticsPeriod
                    }
                })
                this.chartData = data
            } finally {
                this.analyticsLoading = false
            }
        }
    }
}
</script>
