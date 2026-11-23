<template>
    <div>
        <UiSectionHeader>{{ section?.name ?? 'Globala inställningar' }}</UiSectionHeader>
        <UiCard class="max-w-2xl">
            <p
                v-if="error"
                class="mb-4 text-red-600"
                role="alert"
            >
                {{ error }}
            </p>
            <p v-if="loading">
                Laddar...
            </p>
            <template v-else-if="section">
                <div
                    class="flex flex-wrap gap-3 mb-5"
                    aria-label="Språk"
                >
                    <button
                        v-for="(locale, key) in locales"
                        :key="key"
                        type="button"
                        :class="activeLocale === key ? 'font-bold text-royal-500' : 'text-gray-600'"
                        :aria-pressed="activeLocale === key"
                        @click="activeLocale = key"
                    >
                        {{ locale.native }}
                    </button>
                </div>
                <div
                    v-if="groups.length"
                    class="flex flex-wrap gap-4 mb-5 border-b border-gray-200"
                >
                    <button
                        v-for="group in groups"
                        :key="group"
                        type="button"
                        class="pb-2"
                        :class="activeGroup === group ? 'border-b-2 border-royal-400 font-semibold' : ''"
                        :aria-pressed="activeGroup === group"
                        @click="activeGroup = group"
                    >
                        {{ group }}
                    </button>
                </div>
                <p v-if="!section.fields.length">
                    Inga fält har skapats för denna sektion.
                </p>
                <div
                    v-for="field in visibleFields"
                    :key="field.key"
                    class="mb-6"
                >
                    <FInput
                        v-if="field.type === 'text' || field.type === 'textarea'"
                        :model-value="contents[activeLocale]?.[field.key]"
                        :type="field.type"
                        :label="field.name"
                        :name="field.key"
                        @update:model-value="value => setFieldValue(field, value)"
                    />
                    <div
                        v-else-if="field.type === 'image'"
                        class="w-3/4"
                    >
                        <FImageInput
                            :model-value="contents[activeLocale]?.[field.key]"
                            :label="field.name"
                            :name="field.key"
                            :field-key="field.key"
                            :model-id="section.id"
                            @update:model-value="value => setFieldValue(field, value)"
                        />
                    </div>
                </div>
                <button
                    type="button"
                    class="fabriq-btn btn-royal px-6 py-2"
                    :disabled="saving || !activeLocale"
                    @click="save"
                >
                    {{ saving ? 'Sparar...' : 'Spara aktuellt språk' }}
                </button>
            </template>
        </UiCard>
    </div>
</template>

<script setup>
import axios from 'axios'
import { computed, onActivated, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useConfigStore } from '@fabriq/stores'
import { toast } from '@fabriq/components/toast/toast'

const route = useRoute()
const config = useConfigStore()
const section = ref(null)
const contents = ref({
})
const activeLocale = ref('')
const activeGroup = ref('')
const loading = ref(true)
const saving = ref(false)
const error = ref('')
const locales = computed(() => config.supportedLocales)
const groups = computed(() => [...new Set((section.value?.fields ?? []).map(field => field.group || 'Default'))])
const visibleFields = computed(() => section.value.fields.filter(field => (field.group || 'Default') === activeGroup.value))

onActivated(async () => {
    loading.value = true
    section.value = null
    contents.value = {
    }
    error.value = ''

    try {
        await config.initiated
        const localeKeys = Object.keys(locales.value)

        if (!localeKeys.length) {
            throw new Error('No enabled locales')
        }

        const results = await Promise.all(localeKeys.map(async locale => {
            const response = await axios.get(`/api/admin/globals/${encodeURIComponent(route.params.handle)}`, {
                params: {
                    locale_override: locale,
                },
            })

            return [locale, response.data.data]
        }))

        section.value = results[0][1]
        contents.value = Object.fromEntries(results.map(([locale, data]) => [locale, data.content]))
        activeLocale.value = localeKeys.includes(config.activeLocale) ? config.activeLocale : localeKeys[0]
        activeGroup.value = groups.value[0] ?? ''
    } catch (exception) {
        console.error(exception)
        error.value = 'Kunde inte hämta globala inställningar.'
    } finally {
        loading.value = false
    }
})

function setFieldValue(field, value) {
    const localeKeys = field.translated ? [activeLocale.value] : Object.keys(contents.value)

    for (const locale of localeKeys) {
        contents.value[locale][field.key] = value
    }
}

async function save() {
    saving.value = true
    error.value = ''

    try {
        const content = Object.fromEntries(section.value.fields.map(field => {
            const value = contents.value[activeLocale.value][field.key]

            return [field.key, field.type === 'image' ? value?.id ?? null : value ?? null]
        }))

        await axios.put(`/api/admin/globals/${encodeURIComponent(section.value.handle)}`, {
            locale: activeLocale.value,
            content,
        })
        toast.success({
            title: 'Globala inställningar har sparats',
        })
    } catch (exception) {
        console.error(exception)
        error.value = exception.response?.data?.message ?? 'Kunde inte spara globala inställningar.'
    } finally {
        saving.value = false
    }
}
</script>
