<template>
    <div>
        <select
            id="locales"
            v-model="activeLocale"
            class="w-full fabriq-input"
            name="locales"
            @change="$emit('change', activeLocale)"
        >
            <option
                v-for="(locale, key) in locales"
                :key="key"
                :value="key"
            >
                {{ locale.native }}
            </option>
        </select>
    </div>
</template>
<script>
import { useConfigStore } from '@/stores';

export default {
    name: 'FLocaleSelect',
    setup() {
        const configStore = useConfigStore();

        return { configStore }
    },
    computed: {
        locales () {
            return this.configStore.supportedLocales;
        },
        activeLocale: {
            get () {
                return this.configStore.activeLocale;
            },
            set (value) {
                this.configStore.setActiveLocale(value);
            }
        }
    }
}
</script>
