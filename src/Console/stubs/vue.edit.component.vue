<template>
    <AdminLayout :title="pageTitle">
        <AdminShellHeader
            class="mb-4"
            :subtitle="{{ modelVariable }}.name || null"
            :title="pageTitle"
        >
            <template #tools>
                <div class="space-x-4 whitespace-nowrap">
                    <Link
                        class="fabriq-btn btn-link px-6 py-2.5 leading-none"
                        href="/admin/{{ pluralModelRoute }}"
                    >
                        Avbryt
                    </Link>
                    <button
                        class="fabriq-btn btn-royal px-6 py-2.5 leading-none"
                        disabled
                        type="button"
                    >
                        Spara
                    </button>
                </div>
            </template>
        </AdminShellHeader>

        <AdminCard>
            <template #header>
                Information
            </template>

            <div class="grid gap-6 md:grid-cols-3">
                <div>
                    <label
                        class="mb-2 block text-sm font-semibold text-gray-700"
                        for="{{ modelVariable }}-name"
                    >
                        Namn
                    </label>
                    <input
                        id="{{ modelVariable }}-name"
                        v-model="{{ modelVariable }}.name"
                        class="fabriq-input"
                        type="text"
                    >
                </div>
            </div>

            <p class="mt-6 text-sm text-gray-500">
                Byt ut platshållaren med riktig fetch/save-logik och de fält som {{ swedishName }} faktiskt behöver.
            </p>
        </AdminCard>
    </AdminLayout>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { reactive } from 'vue'

import AdminCard from '../../../components/AdminCard.vue'
import AdminShellHeader from '../../../components/AdminShellHeader.vue'
import AdminLayout from '../../../layouts/AdminLayout.vue'

const props = defineProps<{
    pageTitle: string
    {{ modelVariable }}: {
        id: number | null
        name: string
    }
}>()

const {{ modelVariable }} = reactive({
    ...props.{{ modelVariable }},
})
</script>
