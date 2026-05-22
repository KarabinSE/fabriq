<template>
    <div>
        <EditModal
            width="max-w-3xl"
            name="blockTypeEditModal"
            @validated="updateBlockType"
        >
            <div
                v-if="activeItem"
                class="grid grid-cols-2 gap-6 my-6"
            >
                <div class="space-y-6">
                    <FInput
                        ref="nameInput"
                        v-model="activeItem.name"
                        label="Namn"
                        rules="required"
                        name="name"
                    />
                    <FInput
                        ref="nameInput"
                        v-model="activeItem.component_name"
                        label="Komponentnamn"
                        rules="required"
                        name="component_name"
                    />
                    <FSelect
                        v-model="activeItem.options.hidden_for"
                        label="Dold för"
                        multiple
                        :options="templates"
                        option-label="name"
                        :reduce-fn="item => item.slug"
                        name="hidden_for"
                    />
                    <FSelect
                        v-model="activeItem.options.recommended_for"
                        label="Rekommenderad för"
                        multiple
                        :options="templates"
                        option-label="name"
                        :reduce-fn="item => item.slug"
                        name="recommended_for"
                    />
                    <FSelect
                        v-model="activeItem.options.visible_for"
                        label="Synlig för"
                        multiple
                        :options="templates"
                        option-label="name"
                        :reduce-fn="item => item.slug"
                        name="visible_for"
                    />

                    <FSwitch
                        v-model="activeItem.has_children"
                        label=""
                        column-layout
                        rules=""
                    >
                        Har barn
                    </FSwitch>
                </div>

                <div class="space-y-6">
                    <FBlockTypeImageInput
                        v-model="activeItem.image"
                        label="Bild"
                        :preview-src="activeItem.preview_src"
                    />

                    <FInput
                        v-model="activeItem.base_64_svg"
                        label="Base 64 svg"
                        rules=""
                    />

                    <div
                        v-if="activeItem.base_64_svg"
                        class="w-full !my-1"
                    >
                        <img
                            :src="`data:image/svg+xml;base64,` + activeItem.base_64_svg"
                            class="w-full p-2 text-gray-500 italic text-xs"
                            alt="felaktig base_64 svg"
                        >
                    </div>
                </div>
            </div>
        </EditModal>

        <CreateModal
            :show="showCreate"
            focus-ref="nameInput"
            name="createBlockTypeModal"
            width="max-w-2xl"
            @opened="$refs.nameInput.$refs.input.focus()"
            @validated="createItem"
        >
            <template #title>
                Lägg till blocktyp
            </template>
            <div class="grid grid-cols-2 gap-6">
                <FInput
                    ref="nameInput"
                    v-model="newItem.name"
                    label="Namn"
                    rules="required"
                    name="name"
                    placeholder="Kort"
                />
                <FInput
                    v-model="newItem.component_name"
                    label="Komponentnamn"
                    rules="required"
                    placeholder="CardBlock"
                    name="name"
                />
                <FSwitch
                    v-model="newItem.has_children"
                    label=""
                    column-layout
                    rules=""
                >
                    Har barn
                </FSwitch>
            </div>
        </CreateModal>

        <UiSectionHeader class="mb-4">
            Blocktyper
            <template #tools>
                <button
                    type="button"
                    class="fabriq-btn ml-10  btn-royal py-2.5 px-4 inline-flex items-center"
                    @click="$vfm.show('createBlockTypeModal')"
                >
                    Lägg till blocktyp
                </button>
            </template>
        </UiSectionHeader>
        <UiCard
            :padding="false"
            class="pb-4"
        >
            <template #header>
                <div class="flex justify-between px-6">
                    Blocktyper
                </div>
            </template>
            <FTable
                :columns="columns"
                :options="tableOptions"
                :rows="blockTypes"
                class="mb-8"
                @change-page="setPage"
                @row-clicked="handleRowClick"
                @sort="setSort"
            >
                <template #default="{ row: item, prop }">
                    <span
                        v-if="prop == 'edit'"
                        class="flex items-start justify-end space-x-5"
                    >
                        <FConfirmDropdown
                            confirm-question="Vill du ta bort denna sida?"
                            @confirmed="deleteItem(item)"
                        >
                            <TrashIcon
                                class="w-6 h-6 text-gray-800 hover:text-red-500"
                                thin
                            />
                        </FConfirmDropdown>
                    </span>
                    <span v-else-if="prop == 'created_at'">{{ item.created_at | localTime }}</span>
                </template>
            </FTable>
        </UiCard>
    </div>
</template>
<script>
import EditModal from '@/blockTypes/EditModal.vue'
import FBlockTypeImageInput from '@/components/forms/FBlockTypeImageInput.vue'
import BlockType from '@/models/BlockType'
import Template from '@/models/Template'
function defaultCreationObject () {
    return {
        name: '',
        has_children: false,
    }
}
function defaultOptionsStructure () {
    return {
        recommended_for: [],
        hidden_for: [],
        visible_for: [],
    }
}
export default {
    name: 'BlockTypesIndex',
    components: { EditModal, FBlockTypeImageInput },
    data () {
        return {
            showEditModal: false,
            activeItem: {
                options: {}
            },
            templates: [],
            blockTypes: [],
            queryParams: {
                number: 20,
                page: 1,
                sort: 'name',
            },
            showCreate: false,
            newItem: {
                name: '',
                has_children: false,
            },
            tableOptions: {
                defaultSort: 'name',
                sortDescending: true,
                clickableRows: true,
                shadow: false
            },
            columns: [
                {
                    sortable: true,
                    title: 'Namn',
                    key: 'name'
                },
                {
                    title: 'Skapad',
                    key: 'created_at',
                    sortable: true
                },
                {
                    title: '',
                    key: 'edit',
                    tdClasses: 'text-right',
                    thClasses: 'text-right'
                }
            ],
        }
    },
    activated () {
        this.fetchItems()
    },
    methods: {
        async fetchItems () {
            try {
                const payload = {
                    params: this.queryParams
                }
                const { data, meta } = await BlockType.index(payload)

                this.blockTypes = data
            } catch (error) {
                console.error(error)
            }
        },
        async deleteItem (item) {
            try {
                await BlockType.destroy(item.id)
                this.fetchItems()
            } catch (error) {
                console.error(error)
            }
        },
        async createItem () {
            try {
                const { data } = await BlockType.store(this.newItem)
                this.$toast.success({
                    title: 'Blocktypen har skapats!',
                    buttonText: 'Redigera till objektet',
                    onClick: () => this.handleRowClick(data)
                })
                this.$vfm.hide('createBlockTypeModal')
                this.resetCreateModal()
                this.fetchItems()
            } catch (error) {
                if (error.response.status === 422) {
                    this.$refs.observer.validate()
                }
                console.error(error)
            }
        },
        setSort (sort) {
            this.queryParams.sort = sort
            this.fetchItems()
        },
        setPage (pageNumber) {
            this.queryParams.page = pageNumber
            this.fetchItems()
        },
        handleRowClick (item) {
            this.activeItem = null
            this.$nextTick(() => {
                this.activeItem = structuredClone(item)
                this.openEditModal()
            })
        },
        async openEditModal() {
            this.activeItem.options = {...defaultOptionsStructure, ...this.activeItem.options}
            const payload = {
                params: {
                    'filter[type]': 'page'
                }
            }
            const { data } = await Template.index(payload)
            this.templates = data
            this.showEditModal = true
            this.$vfm.show('blockTypeEditModal')
        },
        clearSearch () {
            this.queryParams['filter[search]'] = ''
            this.queryParams.page = 1
            this.fetchItems()
        },
        resetCreateModal () {
            this.newItem = { ...defaultCreationObject() }
        },
        async updateBlockTypeImage() {
            const formData = new FormData()

            // the activeItem.image property is only added when updated or removed.
            // The image is only updated on the backend if the property exists on activeItem
            // image: null -> removes the image
            // image: File -> replaces the image
            if(this.activeItem.hasOwnProperty('image')) {
                formData.append('image', this.activeItem.image)
            }

            try {
                await BlockType.update(this.activeItem.id, formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                })
            } catch (error) {
                console.error(error)
            }
        },
        async updateBlockTypeData() {
            try {
                await BlockType.update(this.activeItem.id, {
                    ...this.activeItem,
                    image: undefined,
                })
            } catch (error) {
                console.error(error)
            }
        },
        async updateBlockType() {
            await this.updateBlockTypeImage()
            await this.updateBlockTypeData()
            this.$toast.success({title: 'Blocktypen har uppdaterats!'})
            this.$vfm.hide('blockTypeEditModal')
            this.fetchItems()
        }
    }
}
</script>
