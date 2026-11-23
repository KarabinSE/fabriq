<template>
    <FModal
        name="video-modal"
        width="max-w-5xl"
        overflow="overflow-auto"
        @before-open="initModal"
        @closed="resetVideo"
    >
        <template #title>
            <div class="flex items-center justify-between flex-1">
                <span
                    class="text-xl text-gray-700"
                    v-text="'Redigera video'"
                />
                <button
                    type="button"
                    class="relative z-20 mr-6"
                >
                    <FConfirmDropdown
                        confirm-question="Vill du ta bort bilden?"
                        @confirmed="deleteVideo"
                    >
                        <TrashIcon
                            thin
                            class="w-6 h-6 mt-1 text-gray-800 transition-colors duration-150 hover:text-red-500"
                        />
                    </FConfirmDropdown>
                </button>
            </div>
        </template>
        <template #actions="{close}">
            <div class="flex justify-end space-x-4">
                <div class="flex space-x-4">
                    <button
                        class="px-8 py-2.5 leading-none fabriq-btn btn-link"
                        @click="close"
                    >
                        Stäng
                    </button>
                    <FButton
                        :click="updateVideo"
                        class="px-8 py-2.5 leading-none fabriq-btn btn-royal"
                    >
                        Spara
                    </FButton>
                </div>
            </div>
        </template>
        <div class="relative z-40 py-2">
            <div class="grid grid-cols-12 gap-x-6">
                <form
                    class="flex flex-col col-span-4 gap-y-4"
                    @submit.prevent="updateVideo"
                >
                    <FInput
                        v-model="video.name"
                        rules="required"
                        name="name"
                        label="Filnamn"
                        :suffix="'.' + video.extension"
                    />
                    <FInput
                        v-model="video.alt_text"
                        label="Alt-text"
                        name="alt_text"
                        help-text="Alt-text visas b.la. för skärmläsare"
                    />
                    <FInput
                        v-model="video.caption"
                        label="Text"
                        name="caption"
                    />
                    <FSelect
                        v-model="video.tags"
                        mode="tags"
                        label="Taggar"
                        name="availableTags"
                        object
                        :create-option="tag => ({ name: tag.name, value: null, type: 'videos'})"
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
                <div class="col-span-8">
                    <div class="flex justify-end mb-1 space-x-2">
                        <UiBadge>
                            {{ formatFileSize(video.size) }}
                        </UiBadge>
                        <UiBadge v-show="video.width">
                            {{ video.width }}×{{ video.height }}px
                        </UiBadge>
                    </div>
                    <video
                        controls
                        :poster="video.poster_src"
                        class="w-full"
                        :src="video.src"
                    >
                        Your browser does not support the video tag.
                    </video>
                </div>
            </div>
        </div>
    </FModal>
</template>

<script>
import Download from '@fabriq/models/Download.js'
import Video from '@fabriq/models/Video.js'
import Tag from '@fabriq/models/Tag.js'
import * as formatters from '@fabriq/utils/formatters'
import { toast } from '@fabriq/components/toast/toast'
import { useEventBus } from '@fabriq/services/eventBus'

export default {
    name: 'VideoModal',
    props: {
        item: {
            type: Object,
            default: () => {},
        },

    },

    emits: ['item-deleted'],

    setup() {
        const eventBus = useEventBus()

        return {
            eventBus,
        }
    },

    data () {
        return {
            video: {
                id: 0,
                size: 0,
                tags: [],
            },

            availableTags: [],
        }
    },

    computed: {
        nameTags () {
            return this.video.tags?.map(item => {
                return item.name
            })
        },
    },

    methods: {
        ...formatters,
        async fetchVideo () {
            try {
                const payload = {
                    params: {
                        include: 'tags',
                    },
                }
                const { data } = await Video.show(this.item.id, payload)

                this.video = data
            } catch (error) {
                console.error(error)
            }
        },

        async initModal () {
            try {
                const promises = [
                    this.fetchVideo(this.item.id),
                    this.fetchVideoTags(),
                ]

                await Promise.all(promises)
            } catch (error) {
                console.error(error)
            }
        },

        async fetchVideoTags () {
            try {
                const payload = {
                    params: {
                        'filter[type]': 'videos',
                    },
                }
                const { data } = await Tag.index(payload)

                this.availableTags = data
            } catch (error) {
                console.error(error)
            }
        },

        resetVideo () {
            this.video = {
                id: 0,
                size: 0,
                tags: [],
            }
        },

        async updateVideo () {
            try {
                const transformedVideo = {
                    ...this.video,
                    tags: this.nameTags,
                }

                await Video.update(this.video.id, transformedVideo)
                this.eventBus.emit('video-updated')
                toast.success({
                    title: 'Videon har uppdaterats!',
                })
                this.closeModal()
            } catch (error) {
                console.error(error)
            }
        },

        async deleteVideo () {
            try {
                await Video.destroy(this.video.id)
                this.eventBus.emit('video-updated')
                toast.success({
                    title: 'Videon har raderats',
                })
                this.$emit('item-deleted')
                this.closeModal()
            } catch (error) {
                toast.error({
                    title: 'Kunde inte radera videon',
                })
                console.error(error)
            }
        },

        async downloadFile () {
            const payload = {
                params: {
                    type: 'videos',
                },
            }
            const { data, headers } = await Download.show(this.video.id, payload)

            await Download.handleBlobDownload(data, headers)
        },

        closeModal() {
            this.$vfm.close('video-modal')
        },
    },
}
</script>
