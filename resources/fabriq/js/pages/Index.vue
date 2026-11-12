<template>
    <div>
        <UiSectionHeader class="mb-4">
            Sidor
            <template #tools>
                <button
                    type="button"
                    class="fabriq-btn btn-royal py-2.5 px-4 inline-flex items-center"
                    @click="$vfm.show('createPageModal')"
                >
                    Lägg till sida
                </button>
            </template>
        </UiSectionHeader>
        <CreateModal
            name="createPageModal"
            width="max-w-xl"
            @before-open="fetchTemplates"
            @closed="resetCreateModal"
            @validated="createPage"
            @opened="$refs.nameInput.$refs.input.focus()"
        >
            <template #title>
                Lägg till sida
            </template>
            <FInput
                ref="nameInput"
                v-model="newPage.name"
                label="Namn"
                rules="required"
                class="mb-6"
                name="name"
            />
            <FSelect
                v-model="newPage.template"
                name="template_id"
                :options="templates"
                placeholder="Vänligen välj"
                option-label="name"
                class="mb-6"
                rules="required"
                value-key="id"
                label="Sidtyp"
                :reduce-fn="item => item"
            />
        </CreateModal>
        <UiCard
            class="pb-4"
        >
            <template #header>
                <div class="flex justify-between ">
                    Sidor
                </div>
            </template>
            <div
                v-if="pageTree.length === 0"
                class="flex items-center justify-center h-48 border-2 border-dashed rounded border-royal-200"
            >
                <div class="flex flex-col items-center">
                    <div class="mb-4 text-xl font-light">
                        Inga sidor har lagts till ännu
                    </div>
                    <button
                        class="flex items-center text-sm link"
                        @click="$vfm.show('createPageModal')"
                    >
                        <PlusIcon class="size-5 mr-2" />Lägg till sida
                    </button>
                </div>
            </div>
            <Draggable
                v-model="pageTree"
                tree-line
                trigger-class="drag-item"
                keep-placholder
                @change="updateTree"
            >
                <template
                    #default="{ node }"
                >
                    <div class="flex items-center w-full justify-between  text-sm border border-gray-300 rounded group py-2 px-2 mb-2">
                        <div class="flex items-center">
                            <GripVerticalIcon class="w-4 h-4 ml-1 mr-2 text-gray-300 drag-item cursor-grab" />

                            <div
                                :class="node.published ? 'bg-green-400' : 'bg-gray-300'"
                                class="w-2 h-2 rounded-full mr-2"
                            />
                            <RouterLink :to="{ name: 'pages.edit', params: {id: node.id } }">
                                {{ node.name }}
                            </RouterLink>
                        </div>
                        <div>
                            <div class="flex items-center justify-end space-x-4 ">
                                <div class="font-medium text-gray-400">
                                    <UiBadge class="mr-8">
                                        {{ node.template.name }}
                                    </UiBadge>
                                </div>
                                <button
                                    v-tooltip.bottom="{ delay: { show: 300, hide: 100 }, content: 'Klona sida' }"
                                    class="focus:outline-none"
                                    @click.stop="clonePage(item)"
                                >
                                    <CloneIcon
                                        thin
                                        class="h-5"
                                    />
                                </button>
                                <RouterLink
                                    :to="{name: 'pages.edit', params: {id: node.id }}"
                                    class="flex items-center justify-end link"
                                >
                                    <PenToSquareIcon
                                        thin
                                        class="w-5 h-5 text-gray-800"
                                    />
                                </RouterLink>
                                <FConfirmDropdown
                                    confirm-question="Vill du ta bort denna sida?"
                                    @confirmed="deletePage(node)"
                                >
                                    <TrashIcon
                                        class="w-5 h-5 mt-1 text-gray-800 hover:text-red-500"
                                        thin
                                    />
                                </FConfirmDropdown>
                            </div>
                        </div>
                    </div>
                </template>
            </Draggable>
        </UiCard>
    </div>
</template>
<script>
import { Draggable } from "@he-tree/vue/vue2";
import "@he-tree/vue/style/default.css";
import Page from '@fabriq/models/Page.js'
import PageTree from '@fabriq/models/PageTree.js'
import Template from '@fabriq/models/Template.js'
import '@fabriq/plugins/vue-nestable/vue-nestable.css'
import VueNestable from '@fabriq/plugins/vue-nestable/VueNestable.vue'
import VueNestableHandle from '@fabriq/plugins/vue-nestable/VueNestableHandle.vue'
import * as formatters from '@fabriq/utils/formatters'

function defaultCreationObject () {
    return {
        name: '',
        template: null
    }
}
export default {
    name: 'PagesIndex',
    components: { Draggable },
    data () {
        return {
            pages: [],
            pageTree: [],
            pagination: {},
            pageToClone: {},
            newPage: {
                name: '',
                template: null
            },
            templates: [],
            queryParams: {
                number: 10,
                page: 1,
                include: 'template',
                'filter[search]': '',
                sort: 'name'
            },
            tableOptions: {
                clickableRows: false,
                defaultSort: 'name',
                sortDescending: false,
                shadow: false
            },
            columns: [
                {
                    sortable: true,
                    title: 'ID',
                    key: 'id',
                    tdClasses: 'w-20'
                },
                {
                    title: 'Namn',
                    key: 'name',
                    tdClasses: 'text-gray-900 font-medium',
                    sortable: true
                },
                {
                    title: 'Sidmall',
                    key: 'template_name'
                },
                {
                    title: 'Uppdaterad',
                    key: 'updated_at',
                    sortable: true
                },
                {
                    title: '',
                    key: 'edit',
                    tdClasses: 'text-right '
                }
            ],
            localizedContent: {}
        }
    },
    activated () {
        // this.fetchPages()
        this.fetchPageTree()
    },
    methods: {
        ...formatters,
        setSort (sort) {
            this.queryParams.sort = sort
            this.fetchPages()
        },
        setPage (pageNumber) {
            this.queryParams.page = pageNumber
            this.fetchPages()
        },
        async fetchPageTree () {
            try {
                const { data } = await PageTree.index()
                this.pageTree = data
            } catch (error) {
                console.error(error)
            }
        },
        async fetchPages () {
            try {
                const payload = {
                    params: this.queryParams
                }
                const { data, meta } = await Page.index(payload)
                this.pages = data
                this.pagination = meta
            } catch (error) {
                console.log(error)
            }
        },
        async clonePage(page, parameters = {}) {
            await this.fetchPage(page.id)

            let localizedContent = {}
            Object.keys(this.pageToClone.localizedContent.data).forEach((item) => {
                localizedContent[item] = { ...this.pageToClone.localizedContent.data[item].content }
            })
            const payload = {
                localizedContent: localizedContent,
            }
            await Page.clone(page.id, {...payload, ...parameters})
            this.$toast.success({ title: 'Sidan har klonats!' })
            this.fetchPageTree()
        },
        async fetchPage (id) {
            const payload = {
                params: {
                    include: 'localizedContent',
                    locale: 'all',
                    append: 'paths'
                }
            }
            try {
                const { data } = await Page.show(id, payload)
                this.pageToClone = data
                console.log(this.pageToClone)
                // this.page = data
                // this.fields = data.template.data.fields
                // this.groupedFields = data.template.data.groupedFields.data
                // localizedContent = { ...data.localizedContent.data }
                // console.log(localizedContent)

                // Object.keys(localizedContent).forEach((item) => {
                //     this.$set(this.localizedContent, item, { ...localizedContent[item].content })
                // })
            } catch (error) {
                console.error(error)
            }
        },
        async deletePage (page) {
            if (page.children.length > 0) {
                this.$toast.info({ title: 'Kunde inte radera sidan', message: 'Sidan har sidor under sig, radera eller flytta dessa först' })
                return false
            }
            try {
                await Page.destroy(page.id)
                this.$toast.success({ title: 'Sidan har raderats ' })
                this.fetchPageTree()
            } catch (error) {
                console.error(error)
            }
        },
        async fetchTemplates () {
            try {
                const payload = {
                    params: {
                        'filter[type]': 'page'
                    }
                }
                const { data } = await Template.index(payload)
                this.templates = data
            } catch (error) {
                console.error(error)
            }
        },
        // async createPage () {
        //     try {
        //         const { data } = await Page.store(this.newPage)
        //         this.$toast.success({
        //             title: 'Sidan har skapats!',
        //             buttonText: 'Gå till sidan',
        //             onClick: () => this.$router.push({ name: 'pages.edit', params: { id: data.id } })
        //         })
        //         this.$vfm.hide('createPageModal')
        //         this.resetCreateModal()
        //         this.fetchPageTree()
        //     } catch (error) {
        //         console.error(error)
        //         if (error.response.status === 422) {
        //             this.$refs.observer.validate()
        //         }
        //     }
        // },
        async createPage () {
            try {
                if(this.newPage.template.locked) {
                    await this.clonePage({id: this.newPage.template.source_model_id}, {name: this.newPage.name})
                    this.$vfm.hide('createPageModal')
                    this.resetCreateModal()

                    return
                }

                this.newPage.template_id = this.newPage.template.id
                const { data } = await Page.store(this.newPage)

                this.$toast.success({
                    title: 'Sidan har skapats!',
                    buttonText: 'Gå till sidan',
                    onClick: () => this.$router.push({ name: 'pages.edit',
                        params: { id: data.id } }),
                })
                this.$vfm.hide('createPageModal')
                this.resetCreateModal()
                this.fetchPageTree()
            } catch (error) {
                console.error(error)

                if (error.response.status === 422) {
                    this.$refs.observer.validate()
                }
            }
        },
        getSubItems (item, items = []) {
            if (item.children) {
                item.children.forEach((index_, index) => {
                    index_.parent_id = item.id
                    index_.sortindex = index * 10
                    items.push(index_)
                    this.getSubItems(index_, items)
                })
            }

            return items
        },
        async updateTree () {
            let items = []
            this.pageTree.forEach((item, index) => {
                item.sortindex = index * 10
                items.push(item)
                items = [...items, ...this.getSubItems(item)]
            })

            try {
                const payload = {
                    tree: this.pageTree
                }
                await PageTree.update(payload)
                // this.fetchPageTree()
            } catch (error) {
                console.error(error)
            }
        },
        clearSearch () {
            this.queryParams['filter[search]'] = ''
            this.queryParams.page = 1
            this.fetchPages()
        },
        resetCreateModal () {
            setTimeout(() => {
                this.newPage = { ...defaultCreationObject() }
            }, 200)
        }
    }
}
</script>
<style>
@reference 'tailwindcss';
 .tree-hline {
  height: 1px;
  width: 10px;
}
.tree-vline {
  margin-top: -8px;
  width: 1px;
}

.drag-placeholder {
    height: 42px;
    @apply bg-gray-50 border-2 border-dotted border-gray-400 rounded;
}

.tree-node-inner {
    @apply mb-2;
}
</style>
