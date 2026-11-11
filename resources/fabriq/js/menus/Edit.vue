<template>
    <div>
        <MenuItemModal
            :is-creating="isCreating"
            @created="fetchTree"
            @updated="fetchTree"
        />
        <UiSectionHeader>
            Redigera meny
            <template #subtitle>
                {{ menu.name }}
            </template>
            <template #tools>
                <div class="space-x-4 whitespace-nowrap">
                    <RouterLink
                        :to="{name: 'menus.index'}"
                        class="inline-flex px-6 py-2.5 my-0 leading-none fabriq-btn btn-link"
                    >
                        Avbryt
                    </RouterLink>
                    <FButton
                        :click="updateTree"
                        class="px-6 py-2.5 leading-none fabriq-btn btn-royal"
                    >
                        Spara
                    </FButton>
                </div>
            </template>
        </UiSectionHeader>
        <div class="min-h-[55px]">
            <FTabs
                v-if="Object.keys(locales).length > 0"
                @change="setLanguage"
            >
                <FTab
                    v-for="(locale, code) in locales"
                    :key="code"
                    :value-key="code"
                    :title="locale.native"
                    class="py-0!"
                />
            </FTabs>
        </div>
        <UiCard>
            <template #header>
                <div class="flex justify-between flex-1">
                    Menypunkter
                    <FButton
                        :click="createMenuItem"
                        class="text-sm"
                        text-button
                        without-loader
                    >
                        <PlusIcon class="w-4 h-4 mr-1" />Lägg till menypunkt
                    </FButton>
                </div>
            </template>
            <div>
                <div
                    v-if="tree.length === 0"
                    class="flex items-center justify-center h-48 border-2 border-dashed rounded border-royal-200"
                >
                    <div class="flex flex-col items-center">
                        <div class="mb-4 text-xl font-light">
                            Inga menypunkter har lagts till ännu
                        </div>
                        <button
                            class="flex items-center text-sm link"
                            @click="createMenuItem"
                        >
                            <PlusIcon class="size-5 mr-2" />Lägg till menypunkt
                        </button>
                    </div>
                </div>
                <Draggable
                    v-model="tree"
                    tree-line
                    class="menu-tree"
                    trigger-class="drag-item"
                >
                    <template
                        #default="{ node: item }"
                    >
                        <div
                            v-show="! deleteQueue.includes(item.id)"
                            class="flex items-center h-14 p-2 border border-gray-300 rounded group"
                        >
                            <div class="h-full flex justify-center items-center px-2 -ml-2 drag-item cursor-grab">
                                <GripVerticalIcon class="size-4 text-gray-300" />
                            </div>

                            <!-- Content -->
                            <div
                                class="flex-1 text-gray-700 flex flex-col gap-1 cursor-pointer overflow-hidden pr-3"
                                @click="editMenuItem(item)"
                            >
                                <div class="text-sm/none font-semibold truncate">
                                    {{ item.title }}
                                </div>
                                <div class="text-xs/none pb-px text-gray-400/80 group-hover:text-gray-600 duration-100 truncate">
                                    <span v-if="item.type === 'internal'">{{ item.page?.name }}</span>
                                    <span v-if="item.type === 'external'">{{ item.path }}</span>
                                    <span v-if="item.type === 'file'">{{ item.content?.data?.file?.file_name ?? 'Ej angivet' }}</span>
                                </div>
                            </div>

                            <div class="flex space-x-3">
                                <div
                                    v-if="item.type !== 'internal'"
                                >
                                    <UiBadge>
                                        <template v-if="item.type === 'external'">
                                            <ExternalLinkIcon class="size-3 mr-1" />
                                            Extern länk
                                        </template>
                                        <template v-if="item.type === 'file'">
                                            <FileIcon class="size-3 mr-1" />
                                            Fil
                                        </template>
                                    </UiBadge>
                                </div>

                                <button
                                    class="flex text-sm focus:outline-none text-gray-500 hover:text-gray-800 duration-100"
                                    @click="editMenuItem(item)"
                                >
                                    <PenToSquareIcon
                                        class="size-5"
                                    />
                                </button>
                                <div class="pr-2">
                                    <FConfirmDropdown
                                        confirm-question="Vill du ta bort menypunkten?"
                                        class="text-gray-500 hover:text-gray-800 duration-100"
                                        @confirmed="deleteConfirmed(item.id)"
                                    >
                                        <TrashIcon
                                            class="size-5"
                                        />
                                    </FConfirmDropdown>
                                </div>
                            </div>
                        </div>
                    </template>
                </Draggable>
            </div>
        </UiCard>
    </div>
</template>
<script>
import { Draggable } from "@he-tree/vue/vue2";
import "@he-tree/vue/style/default.css";
import FileIcon from '@fabriq/icons/FileIcon.vue'
import MenuItemModal from '@fabriq/menus/MenuItemModal.vue'
import Menu from '@fabriq/models/Menu.js'
import MenuItem from '@fabriq/models/MenuItem.js'
import { useConfigStore } from '@fabriq/stores'
export default {
    name: 'MenusEdit',
    components: { MenuItemModal, Draggable },
    beforeRouteLeave (from, to, next) {
        this.$vfm.hide('menu-item-modal')
        this.$destroy()
        next()
    },
    setup () {
        const configStore = useConfigStore()

        return { configStore }
    },
    data () {
        return {
            menu: {},
            id: 0,
            tree: [],
            show: false,
            activeItem: {},
            isCreating: false,
            deleteQueue: [],
            confirm_id: 0
        }
    },
    computed: {
        locales () {
            return this.configStore.supportedLocales
        }
    },
    activated () {
        this.id = this.$route.params.id
        this.fetchMenu()
        this.fetchTree()
    },
    methods: {
        setLanguage (locale) {
            this.configStore.setActiveLocale(locale)
            this.fetchTree()
        },
        async fetchTree () {
            try {
                const { data } = await Menu.showTree(this.id)
                this.tree = data
            } catch (error) {
                console.error(error)
            }
        },
        async fetchMenu () {
            try {
                const payload = {
                    include: 'menuItems'
                }
                const { data } = await Menu.show(this.id, payload)
                this.menu = data
            } catch (error) {
                console.error(error)
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
            this.tree.forEach((item, index) => {
                item.sortindex = index * 10
                items.push(item)
                items = [...items, ...this.getSubItems(item)]
            })

            try {
                const payload = {
                    tree: this.tree
                }
                await Menu.updateTree(this.id, payload)
                this.$toast.success({ title: 'Menyn har uppdaterats!' })
                this.fetchTree()
            } catch (error) {
                console.error(error)
            }
        },
        async editMenuItem (item) {
            this.isCreating = false
            this.$vfm.show('menu-item-modal', item.id)

        },
        createMenuItem () {
            this.activeItem = {}
            this.isCreating = true
            this.$vfm.show('menu-item-modal')
        },
        async deleteConfirmed (id) {
            try {
                await MenuItem.destroy(id)
                this.$toast.success({ title: 'Menypunkten har raderats' })
                this.fetchTree()
            } catch (error) {
                console.error(error)
            }
        }
    }
}
</script>
<style>
@reference 'tailwindcss';
.menu-tree .drag-placeholder {
    height: 56px;
}
</style>
