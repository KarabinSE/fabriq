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
        <UiSectionHeader>
            <div class="flex items-center gap-2">
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
            <template #tools>
                <div>
                    <div class="flex flex-wrap gap-x-4 gap-y-2 whitespace-nowrap">
                        <FButton
                            class="px-6 py-2.5 leading-none text-sm fabriq-btn btn-link"
                            back-button="pages.index"
                        >
                            Avbryt
                        </FButton>
                        <FButton
                            :click="page.published ? publishPage : updateContent"
                            class="px-6 py-2.5 leading-none text-sm fabriq-btn btn-royal"
                        >
                            <span v-text="page.published ? 'Spara & publicera' : wasInitiallyPublished ? 'Avpublicera & spara utkast' : 'Spara utkast'" />
                        </FButton>
                    </div>
                </div>
            </template>
        </UiSectionHeader>
        <div class="flex justify-end">
            <div class="absolute mt-2" />
        </div>
        <div class="grid grid-cols-12 gap-6">
            <div class="col-span-12 lg:col-span-8 xl:col-span-6">
                <FTabs
                    v-if="Object.keys(locales).length > 0"
                    @change="setLanguage"
                >
                    <FTab
                        v-for="(locale, lIndex) in locales"
                        :key="lIndex"
                        :value-key="lIndex"
                        :title="locale.native"
                    >
                        <UiCard
                            collapsible
                            group="pages-ettings"
                            sync-groups
                        >
                            <template #header>
                                Sidinställningar
                            </template>
                            <div class="grid grid-cols-3 gap-x-6 gap-y-6">
                                <FInput
                                    v-model="page.name"
                                    label="Namn"
                                    name="name"
                                />
                                <FInput
                                    v-if="Object.keys(localizedContent).length > 0"
                                    v-model="localizedContent[activeLocale].page_title"
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
                        <div
                            class="grid grid-cols-4 space-x-6"
                        >
                            <div class="col-span-4">
                                <div
                                    v-for="(fieldGroup, index) in filteredGroupedFields"
                                    :key="'g' + index"
                                >
                                    <UiCard
                                        v-if="! repeaterKeys.includes(index)"
                                        :group="index"
                                        sync-groups
                                        collapsible
                                    >
                                        <template #header>
                                            <h3 class>
                                                <span v-if="index === 'meta'">Meta-fält</span>
                                                <span v-else-if="index === 'main_content'">Sidtypsegenskaper</span>
                                                <span v-else>{{ index }}</span>
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
                                                    v-model="localizedContent[lIndex][field.key]"
                                                    :label="field.name"
                                                    :name="field.key"
                                                    class="mb-6"
                                                />
                                                <div v-else-if="field.type == 'textarea'">
                                                    <FInput
                                                        v-model="localizedContent[lIndex][field.key]"
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
                                                        v-model="localizedContent[lIndex][field.key]"
                                                        :label="field.name"
                                                        :name="field.key"
                                                    />
                                                </div>
                                                <div
                                                    v-else-if="field.type == 'image'"
                                                    class="mb-6"
                                                >
                                                    <FImageInput
                                                        v-model="localizedContent[lIndex][field.key]"
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
                                                        v-model="localizedContent[lIndex][field.key]"
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
                                                        v-model="localizedContent[lIndex][field.key]"
                                                        class="col-span-12 lg:col-span-8"
                                                    />
                                                </div>
                                                <div
                                                    v-else-if="field.type == 'switch'"
                                                    class="mb-6"
                                                >
                                                    <FSwitch
                                                        v-model="localizedContent[lIndex][field.key]"
                                                        column-layout
                                                    >
                                                        {{ field.name }}
                                                    </FSwitch>
                                                </div>
                                            </div>
                                        </div>
                                    </UiCard>
                                    <div v-else>
                                        <BlockList
                                            v-if="activeLocale === lIndex"
                                            :key="activeLocale + 'b'"
                                            v-model="localizedContent[activeLocale].boxes"
                                            :locale="lIndex"
                                            :page="page"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </FTab>
                </FTabs>
            </div>
            <div class="col-span-3">
                <div
                    v-if="previewUrl"
                    class="mt-4 rounded border border-gray-200 overflow-hidden"
                >
                    <iframe
                        ref="previewIframe"
                        :src="previewUrl"
                        class="w-full h-[70vh]"
                        title="Preview"
                    />
                </div>
            </div>
            <div class="col-span-12 lg:col-span-4 xl:col-span-3 lg:mt-24">
                <div class="bg-white rounded px-4 py-3 shadow-md grid gap-6">
                    <div class="flex flex-wrap items-stretch gap-4">
                        <FButton
                            :click="openPreviewWindow"
                            spinner-color="text-royal-500"
                            class="flex-1 px-6 py-2.5 leading-none text-sm fabriq-btn btn-outline-royal min-w-[12rem]"
                        >
                            Förhandsgranska
                        </FButton>
                    </div>

                    <hr>
                    <FSwitch
                        v-model="page.published"
                    >
                        Publicerad
                    </FSwitch>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import BlockList from '@/blocks/BlockList.vue'
import RefreshObjectModal from '@/components/modals/RefreshObjectModal.vue'
import Page from '@/models/Page.js'
import PagePaths from '@/pages/PagePaths.vue'
import { usePageStore, useConfigStore, useUiStore } from '@/stores'
import { useDebounceFn } from '@vueuse/core'
import { getCurrentInstance } from 'vue'
import axios from 'axios'

export default {
    name: 'PagesEdit',
    components: {
        PagePaths,
        RefreshObjectModal,
        BlockList,
    },

    beforeRouteLeave (from, to, next) {
        this.$vfm.hide('block-type-modal')
        this.$eventBus.off('block-type-added', this.blockTypeAdded)
        this.$eventBus.off('page-updated-echo', this.askToUpdatePage)
        this.$destroy()
        next()
    },

    setup () {
        const pageStore = usePageStore();

        const configStore = useConfigStore();

        const uiStore = useUiStore();
        const instance = getCurrentInstance();

        const debouncedUpdatePreview = useDebounceFn(() => {
            instance?.proxy?.updatePreview()
        }, 500)

        return {
            pageStore,
            configStore,
            uiStore,
            debouncedUpdatePreview,
        }
    },

    data () {
        return {
            active: false,
            id: 0,
            queryParams: {
                include: 'template,template.groupedFields,slugs,localizedContent',
                locale: 'all',
                append: 'paths',
            },
            paths: [],
            fields: {},
            content: {},
            groupedFields: [],
            repeaterKeys: ['boxes'],
            wasInitiallyPublished: false,
            drag: false,
            localizedContent: {},
            showBlockTypeModalF: false,
            previewWindow: null,
            previewUrl: '',
            previewVisible: false,
        }
    },

    computed: {
        page: {
            set(value) {
                this.pageStore.setPage(value);
            },
            get() {
                return this.pageStore.page;
            }
        },
        openCards() {
            return this.uiStore.openCards;
        },

        config () {
            return this.configStore.config;
        },

        locales () {
            return this.configStore.supportedLocales;
        },

        activeLocale: {
            get () {
                return this.configStore.activeLocale
            },

            set (value) {
                this.configStore.setActiveLocale(value);
            },
        },

        devMode () {
            return this.configStore.devMode;
        },

        filteredGroupedFields () {
            if (!this.groupedFields || typeof this.groupedFields !== 'object') {
                return {}
            }

            return Object.fromEntries(
                Object.entries(this.groupedFields).filter(([groupKey]) => groupKey !== 'main_content')
            )
        },

        lockedBlocks() {
            if(this.devMode) {
                return false
            }

            return this.page.locked
        },
    },

    watch: {
        activeLocale() {
            this.checkBoxesArray()
        },

        localizedContent: {
            deep: true,
            handler() {
                this.debouncedUpdatePreview()
            },
        },
    },

    activated () {
        this.id = this.$route.params.id
        this.$eventBus.on('block-type-added', this.blockTypeAdded)
        this.$eventBus.on('page-updated-echo', this.askToUpdatePage)
        this.fetchPage()
        this.$nextTick(() => {
            if (this.$route.query.openComments) {
                this.$eventBus.emit('open-comment-section')
            }
        })
    },

    methods: {
        askToUpdatePage(event) {
            this.$vfm.show('RefreshObjectModal', event)
        },

        openAllCards () {
            this.$eventBus.emit('open-all-cards')
        },

        checkBoxesArray() {
            if (!this.localizedContent[this.activeLocale]) {
                return
            }

            if (!this.localizedContent[this.activeLocale].boxes) {
                this.$set(this.localizedContent[this.activeLocale], 'boxes', [])
            }
        },


        async updateContent () {
            try {
                const payload = {
                    name: this.page.name,
                    published: this.page.published,
                    localizedContent: { ...this.localizedContent },
                }

                await Page.update(this.id, payload)
                this.wasInitiallyPublished = this.page.published
                this.$toast.success({ title: 'Utkastet har sparats' })
                this.$eventBus.emit('page-updated')
            } catch (error) {
                console.error(error)
            }
        },

        async publishPage () {
            try {
                await this.updateContent(false)
                await Page.publish(this.id)
                this.$toast.success({ title: 'Sidan har publicerats!' })
            } catch (error) {
                console.log(error)
            }
        },

        async fetchPage () {
            const payload = {
                params: this.queryParams,
            }

            try {
                this.page.id = 0
                let localizedContent = null
                const { data } = await Page.show(this.id, payload)

                this.page = data
                this.fields = data.template.data.fields
                this.groupedFields = data.template.data.groupedFields.data
                this.wasInitiallyPublished = data.published
                localizedContent = { ...data.localizedContent.data }
                Object.keys(localizedContent).forEach((item) => {
                    this.$set(this.localizedContent, item, { ...localizedContent[item].content })
                })
                this.checkBoxesArray()
            } catch (error) {
                console.error(error)
            }
        },

        setLanguage (key) {
            this.activeLocale = key
            this.$eventBus.emit('relayout-cards')
            this.$nextTick(() => {
                this.$eventBus.emit('page-updated')
            })
        },

        async openPreviewWindow() {
            try {
                const payload = {
                    name: this.page.name,
                    published: this.page.published,
                    id: this.page.id,
                    content: { ...this.localizedContent },
                }
                const { data } = await axios.post('/api/admin/previews', payload)
                const url = data.preview_url;

                this.previewUrl = url
                this.previewVisible = true

                this.$nextTick(() => {
                    const iframe = this.$refs.previewIframe

                    if (iframe?.contentWindow) {
                        const targetOrigin = new URL(this.previewUrl).origin

                        setTimeout(() => {
                            iframe.contentWindow.postMessage({ type: 'fabriq-preview-ready', url: this.previewUrl }, targetOrigin)
                        }, 500)
                    }
                })
            } catch (error) {
                console.error(error)
            }
        },

        async updatePreview () {
            try {
                const payload = {
                    name: this.page.name,
                    published: this.page.published,
                    id: this.page.id,
                    content: { ...this.localizedContent },
                }

                await axios.patch('/api/admin/previews/' + this.page.id, payload)

                if (this.previewUrl) {
                    const iframe = this.$refs.previewIframe
                    const targetOrigin = new URL(this.previewUrl).origin

                    if (iframe?.contentWindow) {
                        iframe.contentWindow.postMessage({ type: 'fabriq-preview-ready', url: this.previewUrl }, targetOrigin)
                    }
                }
            } catch (error) {
                console.error(error)
            }
        },

        async previewPage () {
            await this.updatePreview()
        },
    },
}
</script>
