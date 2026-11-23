<template>
    <FModal
        name="image-modal"
        width="max-w-5xl"
        overflow="overflow-auto"
        @before-open="initModal"
        @closed="resetImage"
    >
        <template #title>
            <div class="flex items-center justify-between flex-1">
                <div class="flex items-end space-x-4">
                    <span
                        class="text-xl text-gray-700"
                        v-text="'Redigera bild'"
                    />
                    <UiBadge v-if="image.processing">
                        <span class="flex items-center space-x-2">
                            <span>Skapar responsiva versioner</span>
                            <div
                                key="spinner"
                                class="w-3 h-w-3"
                            >
                                <SpinIcon class="animate-spin" />
                            </div>
                        </span>
                    </UiBadge>
                    <UiBadge
                        v-else-if="!image.processing && image.processing_failed"
                        color="red"
                    >
                        <span class="flex items-center space-x-2">
                            <span>Responsiva versioner kunde inte skapas, troligen för stor 🥵</span>
                        </span>
                    </UiBadge>
                </div>

                <button
                    type="button"
                    class="relative z-20 mr-6"
                >
                    <FConfirmDropdown
                        confirm-question="Vill du ta bort bilden?"
                        @confirmed="deleteImage"
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
                        :click="updateImage"
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
                    @submit.prevent="updateImage"
                >
                    <FInput
                        ref="nameInput"
                        v-model="image.name"
                        rules="required"
                        name="name"
                        label="Bildnamn"
                        :suffix="'.' + image.extension"
                    />
                    <FInput
                        v-model="image.alt_text"
                        label="Alt-text"
                        name="alt_text"
                        help-text="Alt-text visas b.la. för skärmläsare"
                    />
                    <FInput
                        v-model="image.caption"
                        name="caption"
                        label="Bildtext"
                    />
                    <FSelect
                        v-model="image.tags"
                        mode="tags"
                        label="Taggar"
                        name="tags"
                        object
                        :create-option="tag => ({ name: tag.name, value: null, type: 'images'})"
                        value-key="name"
                        option-label="name"
                        :options="imageTags"
                    />
                    <div>
                        <FSwitch
                            v-model="image.custom_crop"
                            name="custom_crop"
                        >
                            Anpassa beskärning
                        </FSwitch>
                    </div>
                    <button class="hidden" />
                </form>
                <div class="col-span-8">
                    <div class="flex justify-end mb-1 space-x-2">
                        <UiBadge>
                            {{ formatFileSize(image.size) }}
                        </UiBadge>
                        <UiBadge v-show="image.width">
                            {{ image.width }}×{{ image.height }}px
                        </UiBadge>
                    </div>
                    <div
                        ref="image"
                        class="relative cursor-pointer duration"
                        @click="getCoords"
                        @touchstart="drag.start"
                        @mousedown="drag.start"
                        @touchend="drag.stop"
                        @mouseup="drag.stop"
                        @touchmove="drag.move"
                        @mousemove="drag.move"
                    >
                        <Transition
                            enter-active-class="transition ease-out transform duration-600"
                            enter-class="scale-150 opacity-0"
                            enter-to-class="scale-100 opacity-100"
                            leave-active-class="duration-100 ease-in transform"
                            leave-class="scale-100"
                            leave-to-class="scale-0 opacity-0 "
                        >
                            <div
                                v-if="image.custom_crop"
                                :class="[
                                    'absolute z-10 size-4 -translate-y-1/2 -translate-x-1/2 transition-all transform ease-out border-2 border-white rounded-full shadow bg-gold-500 dot',
                                    drag.dragging ? 'duration-0' : 'duration-75'
                                ]"
                                :style="{left: `${crop.x}%`, top: `${crop.y}%`}"
                            />
                        </Transition>
                        <div class="size-full pointer-events-none select-none">
                            <UiImagePresenter
                                v-if="image.id"
                                :image="image"
                                disable-custom-crop
                                class="w-full mb-2 border border-gray-100 pointer-events-none bg-checkered-lg"
                            />
                        </div>
                    </div>
                    <div class="flex space-x-6">
                        <div>
                            <FButton
                                type="button"
                                class="inline-block px-8 py-2 mb-4 leading-none fabriq-btn btn-outline-royal whitespace-nowrap"
                                spinner-color="text-royal-500"
                                :click="downloadFile"
                            >
                                Ladda ner
                            </FButton>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </FModal>
</template>

<script setup>
import { toast } from '@fabriq/components/toast/toast'
import Download from '@fabriq/models/Download.js'
import Image from '@fabriq/models/Image.js'
import Tag from '@fabriq/models/Tag.js'
import { formatFileSize } from '@fabriq/utils/formatters'
import { ref, useTemplateRef } from 'vue'
import { useVfm } from 'vue-final-modal'
import { useEventBus } from '@fabriq/services/eventBus'
import { reactive } from 'vue'

const eventBus = useEventBus()

const emit = defineEmits(['item-delete'])

defineOptions({
    name: 'BlockTypeModal',
})
const props  =defineProps({
    id: {
        type: Number,
        required: true,
    },
})

const vfm = useVfm()
const image = ref({
    id: 0,
    size: 0,
})

const crop = reactive({
    x: 50,
    y: 50,
})

const imageRef = useTemplateRef('image')
const tags =ref([])
const imageTags =ref([])

async function fetchImage () {
    try {
        const payload = {
            params: {
                include: 'tags',
            },
        }
        const { data } = await Image.show(props.id, payload)

        image.value = data

        crop.x = parseInt(image.value.x_position.slice(0, -1))
        crop.y = parseInt(image.value.y_position.slice(0, -1))
    } catch (error) {
        console.error(error)
    }
}

async function initModal (parameters) {
    eventBus.on('media-finished-processing', evaluateNewStatus)

    try {
        await Promise.all([
            fetchImage(parameters),
            fetchImageTags(),
        ])
    } catch (error) {
        console.error(error)
    }
}

function evaluateNewStatus(event) {
    if(event.image_id !== image.value.id) {
        return
    }

    image.value.processing = event.processing
    image.value.processing_failed = event.processing_failed
}

async function fetchImageTags () {
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

function resetImage () {
    eventBus.off('media-finished-processing', evaluateNewStatus)
    image.value = {
        id: 0,
        size: 0,
    }
}

async function updateImage () {
    try {
        const payload = {
            ...image.value,
            x_position: `${Math.round(crop.x)}%`,
            y_position: `${Math.round(crop.y)}%`,
            tags: image.value.tags.map(tag => tag.name),
        }

        await Image.update(image.value.id, payload)
        eventBus.emit('image-updated')
        toast.success({
            title: 'Bilden har uppdaterats!',
        })
        closeModal()
    } catch (error) {
        console.error(error)
    }
}

async function deleteImage () {
    try {
        await Image.destroy(image.value.id)
        eventBus.emit('image-updated')
        toast.success({
            title: 'Bilden har raderats',
        })
        emit('item-delete')
        closeModal()
    } catch (error) {
        toast.error({
            title: 'Kunde inte radera bilden',
        })
        console.error(error)
    }
}

async function downloadFile () {
    const payload = {
        params: {
            type: 'images',
        },
    }
    const { data, headers } = await Download.show(image.value.id, payload)

    await Download.handleBlobDownload(data, headers)
}

function getCoords (event) {
    if (!image.value.custom_crop) {
        return
    }

    const rect = imageRef.value.getBoundingClientRect()
    const x = event.clientX - rect.left // x position within the element.
    const y = event.clientY - rect.top // y position within the element.

    crop.x = ((x / imageRef.value.offsetWidth) * 100)
    crop.y = ((y / imageRef.value.offsetHeight) * 100)
}

const drag = reactive({
    dragging: false,
    timer: null,
    start() {
        this.timer = setTimeout(() => {
            this.dragging = true
        }, 50)
    },
    stop() {
        clearTimeout(this.timer)
        this.dragging = false
        this.timer = null
    },
    move(event){
        if(!this.dragging) return
        getCoords(event)
    },
})

function closeModal() {
    vfm.close('image-modal')
}
</script>
