<template>
    <FModal
        name="file-modal"
        width="max-w-5xl"
        overflow="overflow-auto"
        @before-open="initModal"
        @closed="resetFile"
    >
        <template #title>
            <div class="flex items-center justify-between flex-1">
                <span
                    class="text-xl text-gray-700"
                    v-text="'Redigera fil'"
                />
                <button
                    type="button"
                    class="relative z-20 mr-6"
                >
                    <FConfirmDropdown
                        confirm-question="Vill du ta bort bilden?"
                        @confirmed="deleteFile"
                    >
                        <TrashIcon
                            thin
                            class="w-6 h-6 mt-1 text-gray-800 transition-colors duration-150 hover:text-red-500"
                        />
                    </FConfirmDropdown>
                </button>
            </div>
        </template>
        <template #actions="{ close }">
            <div class="flex justify-end space-x-4">
                <div class="flex space-x-4">
                    <button
                        class="px-8 py-2.5 leading-none fabriq-btn btn-link"
                        @click="close"
                    >
                        Stäng
                    </button>
                    <FButton
                        :click="updateFile"
                        class="px-8 py-2.5 leading-none fabriq-btn btn-royal"
                    >
                        Spara
                    </FButton>
                </div>
            </div>
        </template>
        <div class="relative z-40 py-2">
            <div class="flex gap-x-6">
                <form
                    class="flex flex-col w-4/12 gap-y-4"
                    @submit.prevent="updateFile"
                >
                    <FInput
                        v-model="file.name"
                        rules="required"
                        name="name"
                        label="Filnamn"
                        :suffix="'.' + file.extension"
                    />
                    <!-- <f-input v-model="file.readable_name"
                             label="Alt-text"
                             help-text="Alt-text visas b.la. för skärmläsare"
                    /> -->
                    <!-- <f-input v-model="file.caption"
                             label="Bildtext"
                    /> -->
                    <FSelect
                        v-model="file.tags"
                        mode="tags"
                        label="Taggar"
                        name="availableTags"
                        object
                        :create-option="tag => ({ name: tag.name, value: null, type: 'files'})"
                        value-key="name"
                        option-label="name"
                        :options="availableTags"
                    />
                    <div>
                        <FButton
                            type="button"
                            class="inline-block px-8 py-2 leading-none fabriq-btn btn-outline-royal"
                            spinner-color="text-royal-500"
                            :click="downloadFile"
                        >
                            Ladda ner
                        </FButton>
                    </div>
                    <button class="hidden" />
                </form>
                <div
                    class="col-span-8 flex flex-col flex-1  w-full overflow-hidden"
                    v-auto-animate
                >
                    <div class="flex justify-end mb-1 space-x-2">
                        <UiBadge>
                            {{ formatFileSize(file.size) }}
                        </UiBadge>
                        <UiBadge v-show="file.width">
                            {{ file.width }}×{{ file.height }}px
                        </UiBadge>
                    </div>
                    <UiImagePresenter
                        v-if="file.thumb_src"
                        key="image-presenter"
                        :image="file"
                        thumbnail
                        class="w-full mb-2 border border-gray-100"
                    />
                </div>
            </div>
        </div>
    </FModal>
</template>
<script>
import { toast } from '@fabriq/components/toast/toast'
import Download from '@fabriq/models/Download.js'
import File from '@fabriq/models/File.js'
import Tag from '@fabriq/models/Tag.js'
import * as formatters from '@fabriq/utils/formatters'
import { useEventBus } from '@fabriq/services/eventBus'

export default {
    name: 'FileModal',

    setup() {
        const eventBus = useEventBus()
        return {eventBus}
    },
    emits: ['item-deleted'],
    props: {
        item: {
            type: Object,
            default: null
        }

    },
    data () {
        return {
            file: {
                id: 0,
                size: 0
            },
            availableTags: []
        }
    },
    computed: {
        tagNames () {
            return this.file.tags.map(item => item.name)
        }
    },
    methods: {
        ...formatters,
        async fetchFile (parameters) {
            try {
                const payload = {
                    params: {
                        include: 'tags'
                    }
                }
                const { data } = await File.show(this.item.id, payload)
                this.file = data
            } catch (error) {
                console.error(error)
            }
        },
        async initModal (parameters) {
            try {
                const promises = [
                    this.fetchFile(parameters),
                    this.fetchFileTags()
                ]
                await Promise.all(promises)
            } catch (error) {
                console.error(error)
            }
        },
        async fetchFileTags () {
            try {
                const payload = {
                    params: {
                        'filter[type]': 'files'
                    }
                }
                const { data } = await Tag.index(payload)
                this.availableTags = data
            } catch (error) {
                console.error(error)
            }
        },
        resetFile () {
            this.file = {
                id: 0,
                size: 0
            }
        },
        async updateFile () {
            try {
                const payload = {
                    ...this.file,
                    tags: [...this.tagNames]
                }
                await File.update(this.file.id, payload)
                this.eventBus.emit('file-updated')
                toast.success({ title: 'Filen har uppdaterats!' })
                this.closeModal()
            } catch (error) {
                console.error(error)
            }
        },
        async deleteFile () {
            try {
                await File.destroy(this.file.id)
                this.eventBus.emit('file-updated')
                toast.success({ title: 'Filen har raderats' })
                this.$emit('item-deleted')
                this.closeModal()
            } catch (error) {
                toast.error({ title: 'Kunde inte radera filen' })
                console.error(error)
            }
        },
        async downloadFile () {
            const payload = {
                params: {
                    type: 'files'
                }
            }
            const { data, headers } = await Download.show(this.file.id, payload)
            await Download.handleBlobDownload(data, headers)
        },
        closeModal() {
            this.$vfm.close('file-modal')
        }
    }
}
</script>
