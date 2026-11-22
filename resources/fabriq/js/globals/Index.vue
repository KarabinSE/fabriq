<template>
    <div>
        <UiSectionHeader>Globala inställningar</UiSectionHeader>
        <UiCard>
            <p
                v-if="error"
                class="text-red-600"
                role="alert"
            >
                {{ error }}
            </p>
            <p v-else-if="loading">
                Laddar...
            </p>
            <p v-else-if="sections.length === 0">
                Inga globala inställningar har skapats.
            </p>
            <ul
                v-else
                class="divide-y divide-gray-200"
            >
                <li
                    v-for="section in sections"
                    :key="section.handle"
                    class="py-3"
                >
                    <RouterLink
                        class="link"
                        :to="{ name: 'globals.edit', params: { handle: section.handle } }"
                    >
                        {{ section.name }}
                    </RouterLink>
                </li>
            </ul>
        </UiCard>
    </div>
</template>

<script setup>
import axios from 'axios'
import { onActivated, ref } from 'vue'

const sections = ref([])
const loading = ref(true)
const error = ref('')

onActivated(async () => {
    loading.value = true
    error.value = ''

    try {
        const response = await axios.get('/api/admin/globals')

        sections.value = response.data.data
    } catch (exception) {
        console.error(exception)
        error.value = 'Kunde inte hämta globala inställningar.'
    } finally {
        loading.value = false
    }
})
</script>
