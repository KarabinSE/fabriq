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
import Page from '@/models/Page.js'
import { useClipboard } from '@vueuse/core';
export default {
    name: 'PagePaths',
    setup() {
        const { copy } = useClipboard({ legacy: true });

        return { copy }
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
    beforeDestroy() {
        this.$eventBus.off('page-updated', this.fetchPaths)
    },
    mounted() {
        this.fetchPaths()
        this.$eventBus.on('page-updated', this.fetchPaths)
    },
    methods: {
        async fetchPaths () {
            const { data } = await Page.paths(this.$route.params.id)
            this.paths = data
        },

        copySuccess () {
            this.$toast.success({ title: 'Länken har kopierats'})
        },

        async copyPath (id) {
            console.log(id)
            await this.copy(id);

            this.copySuccess()
        }
    }
}
</script>
