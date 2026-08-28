<template>
    <div>
        <RefreshObjectModal>
            <template #title>
                Sidan har uppdaterats
            </template>
            <template #default="{ params }">
                <p
                    v-if="params.model"
                    class="text-sm"
                >
                    Sidan har uppdaterats av <span class="font-medium">{{ params.model.updatedByName }}</span>, därför behöver sidan laddas om.
                </p>
            </template>
        </RefreshObjectModal>
        <div>
            <UiSectionHeader>
                <div class="flex items-center gap-2 min-h-10">
                    <div
                        :class="page.published ? 'bg-green-400' : 'bg-gray-300'"
                        class="w-2 h-2 rounded-full  transition-colors"
                    />
                    {{ page.name }}
                </div>

                <template #subtitle>
                    <div class="flex items-end space-x-4">
                        <span v-if="devMode && page.locked">
                            <RouterLink
                                :to="{name: 'pages.edit', params: {id: page.template.data.source_model_id}}"
                                class="flex items-center text-sm link"
                                @click="showBlockTypeModal"
                            >
                                Redigera mall
                            </RouterLink>
                        </span>
                    </div>
                </template>
            </UiSectionHeader>

            <div>
                <div class="min-h-[55px]">
                    <FTabs
                        v-if="Object.keys(locales).length > 0"
                        @change="setLanguage"
                    >
                        <FTab
                            v-for="(locale, lIndex) in locales"
                            :key="lIndex"
                            :value-key="lIndex"
                            :title="locale.native"
                            class="py-0!"
                        />
                    </FTabs>
                </div>

                <div class="relative flex flex-wrap-reverse items-end gap-10 @container">
                    <div class="flex-1">
                        <UiCard
                            collapsible
                            group="pages-settings"
                            sync-groups
                        >
                            <template #header>
                                Sidinställningar
                            </template>
                            <div 
                                class="grid grid-cols-3 gap-6"
                                v-if="Object.keys(pageStore.activeLocaleContent).length > 0"
                            >
                                <FInput
                                    v-model="page.name"
                                    label="Namn"
                                    name="name"
                                />
                                <FInput
                                    v-model="pageStore.activeLocaleContent.page_title"
                                    name="*.page_title"
                                    label="Sidtitel"
                                    help-text="Visas utåt i menyer"
                                />
                                <FInput
                                    v-model="page.template.data.name"
                                    label="Sidtyp"
                                    name="template.data.name"
                                    disabled
                                />
                                <PagePaths
                                    :paths="paths"
                                    class="flex col-span-3 space-x-6 lg:col-span-2"
                                />
                            </div>
                        </UiCard>

                        <div>
                            <div
                                v-for="(fieldGroup, group) in filteredGroupedFields"
                                :key="'g' + group"
                            >
                                <UiCard
                                    v-if="! repeaterKeys.includes(group)"
                                    :group="group"
                                    sync-groups
                                    collapsible
                                >
                                    <template #header>
                                        <h3 class>
                                            <span v-if="group === 'meta'">Meta-fält</span>
                                            <span v-else-if="group === 'main_content'">Sidtypsegenskaper</span>
                                            <span v-else>{{ group }}</span>
                                        </h3>
                                    </template>

                                    <div class="grid grid-cols-12 gap-x-6">
                                        <div
                                            v-for="(field, fieldIndex) in fieldGroup"
                                            :key="'f' + fieldIndex"
                                            :class="[field.options ? field.options.classes : 'col-span-12']"
                                        >
                                            <FInput
                                                v-if="field.type == 'text'"
                                                v-model="pageStore.activeLocaleContent[field.key]"
                                                :label="field.name"
                                                :name="field.key"
                                                class="mb-6"
                                            />
                                            <div v-else-if="field.type == 'textarea'">
                                                <FInput
                                                    v-model="pageStore.activeLocaleContent[field.key]"
                                                    :label="field.name"
                                                    class="mb-6"
                                                    :name="field.key"
                                                    textarea
                                                />
                                            </div>
                                            <div
                                                v-else-if="field.type == 'html'"
                                                class="mb-6"
                                            >
                                                <FEditor
                                                    v-model="pageStore.activeLocaleContent[field.key]"
                                                    :label="field.name"
                                                    :name="field.key"
                                                />
                                            </div>
                                            <div
                                                v-else-if="field.type == 'image'"
                                                class="mb-6"
                                            >
                                                <FImageInput
                                                    v-model="pageStore.activeLocaleContent[field.key]"
                                                    :label="field.name"
                                                    :group="field.options.group"
                                                    :name="field.key"
                                                    :field-key="field.key"
                                                    :model-id="page.id"
                                                />
                                            </div>
                                            <div
                                                v-else-if="field.type == 'video'"
                                                class="mb-6"
                                            >
                                                <FVideoInput
                                                    v-model="pageStore.activeLocaleContent[field.key]"
                                                    :label="field.name"
                                                    :name="field.key"
                                                    :group="field.options.group"
                                                    :field-key="field.key"
                                                    :model-id="page.id"
                                                />
                                            </div>
                                            <div
                                                v-else-if="field.type == 'button'"
                                                class="mb-6"
                                            >
                                                <div>
                                                    <FLabel :name="field.key">
                                                        Knapp
                                                    </FLabel>
                                                </div>
                                                <FButtonItem
                                                    v-model="pageStore.activeLocaleContent[field.key]"
                                                    class="col-span-12 lg:col-span-8"
                                                />
                                            </div>
                                            <div
                                                v-else-if="field.type == 'switch'"
                                                class="mb-6"
                                            >
                                                <FSwitch
                                                    v-model="pageStore.activeLocaleContent[field.key]"
                                                    column-layout
                                                >
                                                    {{ field.name }}
                                                </FSwitch>
                                            </div>
                                        </div>
                                    </div>
                                </UiCard>

                                <BlockList v-else />
                            </div>
                        </div>
                    </div>

                    <div class="@4xl:sticky top-6 w-full @4xl:max-w-sm">
                        <UiCard>
                            <div class="flex flex-col">
                                <div class="flex flex-col items-stretch gap-4">
                                    <FButton
                                        class="flex-1 px-6 py-2.5 leading-none text-sm fabriq-btn btn-link"
                                        back-button="pages.index"
                                        :click="pageStore.resetPage"
                                    >
                                        Avbryt
                                    </FButton>
                                    <FButton
                                        :click="page.published ? publishPage : updateContent"
                                        class="flex-1 px-6 py-2.5 leading-none text-sm fabriq-btn btn-royal whitespace-nowrap @4xl:min-w-[10rem]"
                                    >
                                        <span v-text="page.published ? 'Spara & publicera' : pageStore.wasInitiallyPublished ? 'Avpublicera & spara utkast' : 'Spara utkast'" />
                                    </FButton>
                                </div>

                                <hr class="my-5">
                            
                                <FSwitch
                                    v-model="page.published"
                                >
                                    Publicerad
                                </FSwitch>
                            </div>
                        </UiCard>

                        <div class="flex pt-5">
                            <FButton
                                :click="openPreviewWindow"
                                spinner-color="text-royal-500"
                                class="flex-1 px-6 py-2.5 leading-none text-sm fabriq-btn btn-outline-royal bg-white"
                            >
                                Öppna förhandsgranskning
                            </FButton>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <PagePreview 
            @publish="publishPage"
            @save="updateContent"
        />
    </div>

</template>

<script>
import BlockList from '@/blocks/BlockList.vue'
import RefreshObjectModal from '@/components/modals/RefreshObjectModal.vue'
import PagePaths from '@/pages/PagePaths.vue'
import { usePageStore, useConfigStore, useUiStore } from '@/stores'

import PagePreview from './PagePreview.vue'
import { usePreviewStore } from '@/stores/preview.js'

export default {
    name: 'PagesEdit',
    components: {
        PagePaths,
        RefreshObjectModal,
        BlockList,
        PagePreview,
    },

    beforeRouteLeave (from, to, next) {
        this.previewStore.reset()
        this.$vfm.hide('block-type-modal')
        this.$eventBus.off('page-updated-echo', this.askToUpdatePage)
        this.$destroy()
        next()
    },

    setup () {
        const pageStore = usePageStore();
        const configStore = useConfigStore();
        const uiStore = useUiStore();
        const previewStore = usePreviewStore()

        return {
            pageStore,
            configStore,
            uiStore,
            previewStore,
        }
    },

    data () {
        return {
            active: false,
            pageId: 0,
            paths: [],
            repeaterKeys: ['boxes'],
            drag: false,
            showBlockTypeModal: false,
        }
    },

    computed: {
        page() {
            return this.pageStore.page;  
        },

        openCards() {
            return this.uiStore.openCards;
        },

        locales () {
            return this.configStore.supportedLocales;
        },

        devMode () {
            return this.configStore.devMode;
        },

        filteredGroupedFields () {
            return this.pageStore.filteredGroupedFields
        },
    },

    async activated () {
        this.pageId = this.$route.params.id
        this.$eventBus.on('page-updated-echo', this.askToUpdatePage)
        this.pageStore.resetPage()
        this.pageStore.fetchPage(this.pageId)
        this.$nextTick(() => {
            if (this.$route.query.openComments) {
                this.$eventBus.emit('open-comment-section')
            }
        })
    },

    methods: {
        closePreview() {
            this.previewStore.hidePreview()
        },

        askToUpdatePage(event) {
            this.$vfm.show('RefreshObjectModal', event)
        },

        openAllCards () {
            this.$eventBus.emit('open-all-cards')
        },

        async updateContent () {
            try {
                await this.pageStore.update(this.pageId)
                this.$toast.success({ title: 'Utkastet har sparats' })
                this.$eventBus.emit('page-updated')
            } catch (error) {
                console.error(error)
            }
        },

        async publishPage () {
            try {
                await this.updateContent()
                await this.pageStore.publish(this.pageId)
                this.$toast.success({ title: 'Sidan har publicerats!' })
            } catch (error) {
                console.log(error)
            }
        },

        setLanguage (key) {
            this.configStore.setActiveLocale(key)
            this.$eventBus.emit('relayout-cards')
            this.$nextTick(() => {
                this.$eventBus.emit('page-updated')
            })
        },

        async updatePreview () {
            await this.previewStore.updatePreview()
        },

        async openPreviewWindow() {
            this.previewStore.showPreview()
        },
    },
}
</script>
