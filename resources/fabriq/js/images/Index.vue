<template>
    <div>
        <FModal
            v-model="showTagsModal"
            name="add-tags-modal"
            width="max-w-xl"
            :click-to-close="false"
            @before-open="fetchImageTags"
        >
            <template #title>
                Kategorisera bilder
            </template>
            <FSelect
                v-model="selectedTags"
                mode="tags"
                label="Taggar"
                name="tags"
                object
                :create-option="tag => ({ name: tag.name, value: null, type: 'images'})"
                value-key="name"
                option-label="name"
                :options="imageTags"
            />
            <template #actions>
                <div class="flex justify-end mt-4 space-x-4">
                    <div class="flex space-x-4">
                        <button
                            class="px-8 py-2.5 leading-none fabriq-btn btn-link"
                            @click="showTagsModal = false"
                        >
                            Stäng
                        </button>
                        <FButton
                            :click="addTagsToImages"
                            class="px-8 py-2.5 leading-none fabriq-btn btn-royal"
                        >
                            Spara
                        </FButton>
                    </div>
                </div>
            </template>
        </FModal>
        <UiSectionHeader class="mb-4">
            Bilder
            <template #tools>
                <div class="flex items-end space-x-2">
                    <div>
                        <AddImageFromUrlModal
                            name="addFromUrlModal"
                            @image-added="fetchImages"
                        />
                        <button
                            type="button"
                            class="inline-flex items-center px-2 py-1 text-xs fabriq-btn btn-gold"
                            @click="vfm.open('addFromUrlModal')"
                        >
                            Lägg till från URL
                        </button>
                    </div>
                    <FUpload
                        v-if="uploadInit"
                        class=""
                        endpoint="/api/admin/uploads/images"
                        types="image/*"
                        upload-name="image"
                        @upload-queue-complete="fetchImages"
                    >
                        <template #button="{ openFileDialog }">
                            <button
                                type="button"
                                class="fabriq-btn  btn-royal py-2.5 px-4 inline-flex items-center"
                                @click="openFileDialog"
                            >
                                Ladda upp bilder
                            </button>
                        </template>
                    </FUpload>
                </div>
            </template>
        </UiSectionHeader>
        <UiCard
            :padding="false"
            class="pb-6"
        >
            <template #header>
                <div class="flex justify-between px-6">
                    <div>
                        Uppladdade bilder
                    </div>
                    <div
                        class="flex items-center space-x-6 text-sm transition duration-300"
                        :class="hasCheckedRows ? 'text-gray-700' : 'text-gray-300'"
                    >
                        <Transition name="fade">
                            <span
                                v-if="hasCheckedRows"
                                class="mr-4 leading-none text-gray-600"
                            >Markerade bilder ({{ checkedRows.length }})</span>
                        </Transition>
                        <FButton
                            :click="downloadCheckedItems"
                            :disabled="!hasCheckedRows"
                            class="focus:outline-none"
                            spinner-color="text-royal-500"
                        >
                            <ArrowDownLineIcon
                                class="w-6 h-6 mr-3 "
                                thin
                            />
                            <span class="font-semibold">
                                Ladda ner
                            </span>
                        </FButton>
                        <FButton
                            class="focus:outline-none"
                            without-loader
                            :disabled="!hasCheckedRows"
                            spinner-color="text-royal-500"
                            :click="showModal"
                        >
                            <TagIcon
                                class="w-6 h-6 mr-3 "
                                thin
                            />
                            <span class="font-semibold">
                                Lägg till kategori
                            </span>
                        </FButton>
                        <FConfirmDropdown
                            confirm-question="Vill du ta bort de markerade bilderna?"
                            :disabled="!hasCheckedRows"
                            @confirmed="deleteCheckedImages()"
                        >
                            <div class="flex items-center space-x-3 font-semibold">
                                <TrashIcon
                                    class="w-6 h-6 "
                                    thin
                                />
                                <div>
                                    Ta bort
                                </div>
                            </div>
                        </FConfirmDropdown>
                    </div>
                </div>
            </template>
            <div class="max-w-full overflow-auto">
                <FTable
                    :columns="columns"
                    :rows="images"
                    paginated
                    :pagination="pagination"
                    :options="{shadow: false, defaultSort: 'created_at', sortDescending: true, clickableRows: true, checkableRows: true}"
                    @row-clicked="openImageModal"
                    @change-page="setPage"
                    @check-row="handleRowChecked"
                    @sort="setSort"
                >
                    <template #search>
                        <div class="px-6 border-b border-gray-100">
                            <div class="flex items-center ">
                                <SearchIcon class="w-6 h-6 mr-0 text-gray-300" />
                                <FSearchInput
                                    v-model="queryParams['filter[search]']"
                                    placeholder="Sök…"
                                    class="flex-1 px-6 py-4 text-sm text-gray-600 appearance-none focus:outline-none"
                                    @perform-search="performSearch"
                                    @clear-search="resetSearch"
                                />
                            </div>
                        </div>
                    </template>
                    <template #default="{ row: item, prop }">
                        <span
                            v-if="prop == 'image'"
                            class="block w-20"
                            @click="openImageModal(item)"
                        >
                            <UiImagePresenter
                                :image="item"
                                thumbnail
                                class="w-20 cursor-pointer max-h-16"
                            />
                        </span>
                        <span v-else-if="prop == 'created_at'">
                            {{ formatLocalTime(item.created_at) }}
                        </span>
                        <span
                            v-else-if="prop == 'c_name'"
                            class="flex items-center space-x-3"
                        >
                            <div
                                v-if="! item.processing && item.processing_failed"
                            >
                                <CircleXMarkIcon class="w-4 text-red-500 h-w-4" />
                            </div>
                            <div class="truncate text-ellipsis max-w-64">

                                {{ item.c_name }}
                            </div>
                        </span>
                        <span
                            v-else-if="prop == 'alt_text'"
                            class="flex items-center space-x-3"
                        >
                            <div class="text-xs truncate text-ellipsis max-w-64">
                                {{ item.alt_text }}
                            </div>
                        </span>
                        <span
                            v-else-if="prop == 'tags'"
                            class="flex space-x-2"
                        >
                            <UiBadge
                                v-for="(tag, index) in item.tags"
                                :key="index"
                            >{{ tag.name }}</UiBadge>
                        </span>
                        <span v-else-if="prop == 'size'">
                            {{ formatFileSize(item.size) }}
                        </span>
                        <span v-else-if="prop == 'dimensions'">

                            <UiBadge v-show="item.width">
                                {{ item.width }}×{{ item.height }}px
                            </UiBadge>
                        </span>
                        <span v-else-if="prop == 'edit'">
                            <button
                                class="px-4 py-2 btn-ghost fabriq-btn"
                                @click="openImageModal(item)"
                            ><PenToSquareIcon
                                class="w-6 h-6"
                                thin
                            /></button>
                            <FConfirmDropdown
                                confirm-question="Vill du ta bort denna bilden?"
                                @confirmed="deleteImage(item.id)"
                            >
                                <TrashIcon
                                    class="w-6 h-6 mt-1 text-gray-800 hover:text-red-500"
                                    thin
                                />
                            </FConfirmDropdown>
                        </span>
                    </template>
                </FTable>
            </div>
        </UiCard>
    </div>
</template>

<script setup>
import { toast } from '@fabriq/components/toast/toast'
import AddImageFromUrlModal from '@fabriq/images/AddImageFromUrlModal.vue'
import ImageModal from '@fabriq/images/ImageModal.vue'
import Download from '@fabriq/models/Download.js'
import Image from '@fabriq/models/Image.js'
import Tag from '@fabriq/models/Tag.js'
import { formatFileSize, formatLocalTime } from '@fabriq/utils/formatters'
import { computed, inject } from 'vue'
import { onActivated, ref } from 'vue'
import { useVfm } from 'vue-final-modal'
import { onBeforeRouteLeave } from 'vue-router'
import { useEventBus } from '@fabriq/services/eventBus'
import { createModal } from '@fabriq/helpers/utils'

const eventBus = useEventBus()

defineOptions({
    name: 'ImagesIndex',
})
const vfm = useVfm()


const images = ref([])
const search = ref('')
const uploadInit = ref(true)
const checkedRows = ref([])
const suppressToast = ref(false)
const showTagsModal = ref(false)

const columns = ref([
    {
        key: 'image',
        title: '',
        // tdClasses: 'w-36',
        // thClasses: 'w-36'
    },
    {
        key: 'c_name',
        title: 'Namn',
        tdClasses: 'text-gray-600 font-medium w-24',

        sortable: true,
    },
    {
        key: 'created_at',
        title: 'Uppladdad',
        sortable: true,
    },
    {
        key: 'alt_text',
        title: 'Alt-text',
        sortable: true,
    },
    {
        key: 'tags',
        title: 'Taggar',
        sortable: true,
    },
    {
        key: 'size',
        title: 'Filstorlek',
        sortable: true,
    },
    {
        key: 'dimensions',
        title: 'Storlek',
        sortable: false,
    },
    {
        key: 'edit',
        title: '',
        tdClasses: 'text-right',
        thClasses: 'text-right',
    },
])

const pagination = ref({})

const queryParams = ref({
    number: 15,
    page: 1,
    sort: '-created_at',
    include: 'tags',
    'filter[search]': '',
})
const imageTags = ref([])
const selectedTags = ref([])

onActivated(() =>{
    fetchImages()
    uploadInit.value = true
    eventBus.on('image-updated', fetchImages)
    eventBus.on('media-finished-processing', fetchImages)
})

const hasCheckedRows = computed(() => {
    return checkedRows.value.length > 0
})
const nameTags = computed(() => {
    return selectedTags.value.map(item => {
        return item.name
    })
})

function resetSearch () {
    search.value = ''
    queryParams.value['filter[search]'] = ''
    queryParams.value.page = 1
    fetchImages()
}

async function fetchImages () {
    try {
        const payload = {
            params: queryParams.value,
        }
        const { data, meta } = await Image.index(payload)

        images.value = data
        pagination.value = meta
    } catch (error) {
        console.error(error)
    }
}

async function deleteImage (id) {
    try {
        await Image.destroy(id)

        if (!suppressToast.value) {
            toast.success({
                title: 'Bilden har tagits bort',
            })
            fetchImages()
        }
    } catch (error) {
        console.error(error)
    }
}

function setSort (sort) {
    queryParams.value.sort = sort
    fetchImages()
}

function performSearch () {
    queryParams.value.page = 1
    fetchImages()
}

function setPage (pageNumber) {
    queryParams.value.page = pageNumber
    fetchImages()
}

function openImageModal (item) {
    createModal(ImageModal, {
        id: item.id,
        onItemDeleted: fetchImages,
    })
}

function handleRowChecked (rows) {
    checkedRows.value = rows
}

async function deleteCheckedImages () {
    suppressToast.value = true
    const promises = []

    checkedRows.value.forEach(row => {
        promises.push({
            deleteFunction: deleteImage,
            id: row,
        })
    })
    await Promise.all(
        promises.map(async promise => {
            await promise.deleteFunction(promise.id)
        }),
    )
    suppressToast.value = false
    eventBus.emit('clear-checked-rows')
    toast.success({
        title: 'Bilderna har tagits bort',
    })
    fetchImages()
}

async function downloadCheckedItems () {
    try {
        const payload = {
            params: {
                items: checkedRows.value,
                type: 'images',
            },
        }
        const { data, headers } = await Download.index(payload)

        Download.handleBlobDownload(data, headers)
    } catch (error) {
        console.error(error)
    }
}

function showModal () {
    showTagsModal.value = true
}

async function addTagsToImages () {
    try {
        const payload = {
            models: checkedRows,
            tags: nameTags,
            model_type: 'images',
        }

        await Tag.store(payload)
        toast.success({
            title: 'Taggarna har lagts till!',
        })
        fetchImages()
        showTagsModal.value = false
    } catch (error) {
        console.error(error)
    }
}

async function fetchImageTags () {
    selectedTags.value = []

    try {
        const payload = {
            params: {
                'filter[type]': 'images',
            },
        }
        const { data } = await Tag.index(payload)

        imageTags.value = data
    } catch (error) {
        console.error(error)
    }
}

onBeforeRouteLeave((from, to, next) => {
    eventBus.off('image-updated', fetchImages)
    uploadInit.value = false
    next()
})

</script>
