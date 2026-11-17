<template>
    <div class="relative h-80 w-full">
        <VueChartLine
            :data="chartData"
            :options="chartOptions"
        />
    </div>
</template>
<script>
import {
    CategoryScale,
    Chart as ChartJS,
    Legend,
    LineElement,
    LinearScale,
    PointElement,
    Tooltip
} from 'chart.js'
import { Line as VueChartLine } from 'vue-chartjs'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Tooltip, Legend)

export default {
    name: 'VisitsChart',

    components: {
        VueChartLine
    },

    props: {
        data: {
            type: Array,
            required: true
        },

        options: {
            type: Object,
            required: true
        }
    },
    computed: {
        chartData () {
            return {
                labels: this.data.map(row => row.date),
                datasets: [
                    {
                        label: 'Besökare',
                        data: this.data.map(row => row.visits),
                        borderColor: '#bd9b66',
                        backgroundColor: '#bd9b66',
                        tension: 0.2
                    },
                    {
                        label: 'Sidvisningar',
                        data: this.data.map(row => row.pageViews),
                        borderColor: '#0b3b5b',
                        backgroundColor: '#0b3b5b',
                        tension: 0.2
                    }
                ]
            }
        },
        chartOptions () {
            return {
                responsive: true,
                maintainAspectRatio: false,
                ...this.options
            }
        }
    }
}
</script>
