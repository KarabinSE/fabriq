<template>
    <div class="min-h-[66px]">
        <FInput
            v-for="(path, index) in paths"
            :key="index"
            v-model="paths[index]"
            :label="pathLabelMap[index]"
            class="w-full"
            read-only
        >
            <template #buttonSuffix>
                <FButton
                    :click="() => copyPath(paths[index])"
                    without-loader
                    class="px-3 ring-1 ring-royal-500 rounded-l-none py-2.5 leading-none text-sm fabriq-btn btn-royal"
                >
                    <PasteIcon class="h-4" />
                </FButton>
            </template>
        </FInput>
    </div>
</template>
<script>
import { toast } from '@fabriq/components/toast/toast'
import Page from '@fabriq/models/Page.js'
import { useClipboard } from '@vueuse/core';
import { useEventBus } from '@fabriq/services/eventBus'

export default {
    name: 'PagePaths',
    setup() {
        const { copy } = useClipboard({ legacy: true });
        const eventBus = useEventBus()

        return { copy, eventBus }
    },
    data() {
        return {
            paths: {},
            pathLabelMap: {
                absolute_path: 'Absolut sökväg',
                permalink: 'Permalänk'
            }
        }
    },
    unmounted() {
        this.eventBus.off('page-updated', this.fetchPaths)
    },
    mounted() {
        this.fetchPaths()
        this.eventBus.on('page-updated', this.fetchPaths)
    },
    methods: {
        async fetchPaths () {
            const { data } = await Page.paths(this.$route.params.id)
            this.paths = data
        },

        copySuccess () {
            toast.success({ title: 'Länken har kopierats'})
        },

        async copyPath (id) {
            console.log(id)
            await this.copy(id);

            this.copySuccess()
        }
    }
}
</script>
