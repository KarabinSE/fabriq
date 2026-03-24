<template>
    <AdminLayout :title="pageTitle">
        <AdminShellHeader
            class="mb-4"
            :title="pageTitle"
        >
            <template #tools>
                <button
                    class="fabriq-btn btn-royal px-6 py-2.5 leading-none"
                    disabled
                    type="button"
                >
                    Lägg till {{ swedishName }}
                </button>
            </template>
        </AdminShellHeader>

        <AdminCard>
            <template #header>
                {{ SwedishPluralName }}
            </template>

            <div class="space-y-4 text-sm text-gray-600">
                <p>
                    Inertia-sidan för {{ swedishPluralName }} har skapats. Byt ut platshållaren med riktig serverdata, tabell-UI och mutationer.
                </p>
                <p>
                    Den genereras i rätt runtime nu, inte i den gamla router/store-soppan. Varsågod.
                </p>
            </div>
        </AdminCard>
    </AdminLayout>
</template>

<script setup lang="ts">
import AdminCard from '../../../components/AdminCard.vue'
import AdminShellHeader from '../../../components/AdminShellHeader.vue'
import AdminLayout from '../../../layouts/AdminLayout.vue'

defineProps<{
    pageTitle: string
}>()
</script>
