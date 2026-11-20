<template>
    <span :class="{'opacity-70 cursor-not-allowed': disabled }">
        <span :class="{'pointer-events-none': disabled }">
            <FLabel
                v-if="label"
                :padding="false"
                :name="name"
            >{{ label }}</FLabel>
            <div
                ref="dropElement"
                :class="[
                    'relative flex items-center justify-center transition-colors duration-150 border-dashed rounded-md aspect-video ring-2 ring-inset',
                    hasImage ? 'border-transparent ring-transparent' : 'ring-royal-500',
                    isDraggingOver ? 'bg-royal-100' : 'bg-royal-50'
                ]"
            >
                <div
                    v-if="hasImage"
                    class="absolute inset-0 group"
                >
                    <div class="absolute inset-0 z-10 flex items-end justify-center transition-opacity duration-300 opacity-0 group-hover:opacity-100 ">
                        <button
                            type="button"
                            class="absolute top-0 right-0 flex items-center px-4 py-2 space-x-2 text-sm font-semibold text-white bg-gray-800 rounded-bl-md rounded-tr-md"
                            @click="showImageModal"
                        > <PencilIcon class="w-3.5" /> <span>Redigera</span></button>
                        <div class="flex w-full -mb-px">
                            <button
                                class="flex items-center justify-center w-full px-4 py-4 text-sm font-semibold leading-none text-white transition-colors duration-150 bg-gray-800 focus:outline-none rounded-bl-md hover:bg-gray-900"
                                @click="showMediaPicker"
                            >Byt</button>
                            <button
                                class="flex items-center justify-center w-full px-4 py-4 text-sm font-semibold leading-none text-white transition-colors duration-150 bg-gray-800 focus:outline-none rounded-br-md hover:bg-gray-900"
                                @click="clearImage"
                            >
                                Ta bort
                            </button>
                        </div>
                    </div>
                    <div class="absolute inset-0 transition-opacity duration-300 bg-black rounded-md opacity-0 group-hover:opacity-50 " />
                    <UiImagePresenter
                        thumbnail
                        v-if="localImage"
                        :image="localImage"
                        class="block object-cover w-full h-full rounded-md"
                    />
                </div>
                <div
                    v-else
                    class="absolute inset-0 flex flex-col items-center justify-center"
                >
                    <div
                        class="flex flex-col items-center space-y-2 text-sm "
                    >
                        <button
                            ref="buttonElement"
                            type="button"
                            class="flex px-6 py-2.5 mb-2 text-sm leading-none fabriq-btn btn-royal"
                        >
                            Ladda upp bild
                        </button>
                        <span>eller</span>
                        <button
                            class="cursor-pointer link"
                            type="button"
                            @click="showMediaPicker"
                        >
                            välj från arkivet
                        </button>
                    </div>

                    <FUpload
                        class="absolute bottom-10 pointer-events-none text-sm"
                        :max-items="1"
                        endpoint="/api/admin/uploads/images"
                        types="image/*"
                        upload-name="image"
                        :drop-ref="dropElement"
                        :button-ref="buttonElement"
                        without-button
                        @upload-complete="handleNewImage"
                        @is-over-dropzone="isOverDropzone => isDraggingOver = isOverDropzone"
                    />
                </div>

            </div>
        </span>
    </span>
</template>

<script setup>
import ImageModal from '@fabriq/images/ImageModal.vue'
import Image from '@fabriq/models/Image.js'
import { computed, onMounted, onUnmounted } from 'vue'
import { ref, useTemplateRef } from 'vue'
import { useEventBus } from '@fabriq/services/eventBus'
import { createModal } from '@fabriq/helpers/utils'
import FMediaPicker from '@fabriq/components/forms/FMediaPicker.vue'

const eventBus = useEventBus()
defineOptions({name: 'FImageInput'})

const emit = defineEmits(['item-picked'])
const props = defineProps({
    modelValue: {
        type: [String, Number, Object, Array],
        default: null
    },
    label: {
        type: String,
        default: ''
    },
    modelId: {
        type: Number,
        required: false,
        default: 0
    },
    fieldKey: {
        type: String,
        required: false,
        default: ''
    },
    multiple: {
        type: Boolean,
        default: false
    },
    modelName: {
        type: String,
        default: ''
    },
    buttonText: {
        type: String,
        default: ''
    },
    group: {
        type: String,
        default: ''
    },
    syncSimilar: {
        type: Boolean,
        default: false
    },
    name: {
        type: String,
        default: ''
    },
    disabled: {
        type: Boolean,
        default: false
    },
})

const localImage =  defineModel({
    type: Object,
    default: null
})

const instanceId =  Math.random().toString(20).substring(2, 6)
const isDraggingOver =  ref(false)
const dropElement =  useTemplateRef('dropElement')
const buttonElement =  useTemplateRef('buttonElement')

const hasImage = computed(() => {
    return !!localImage.value?.id
})

onMounted(() => {
    if (props.group) {
        eventBus.on('image-selected', setImage)
    }
})
onUnmounted(() => {
    eventBus.off('image-selected', setImage)
    // $refs.dropElement?.removeEventListener('dragover', handleDragOver)
    // $refs.dropElement?.removeEventListener('dragleave', handleDragLeave)
})
function clearImage () {
    localImage.value = null
    emitImageSelected()
}
async function pickImage (id) {
    if (props.multiple) {
        await pickMultipleImages(id)
    } else {
        await pickSingleImage(id)
    }
}
async function pickMultipleImages (id) {
    try {
        const payload = {
            model_id: props.modelId
        }
        const { data } = await Image.attachToModel(id, modelName, payload)
        emit('item-picked', data)
    } catch (error) {
        console.error(error)
    }
}

async function pickSingleImage (id) {
    try {
        const payload = {
            key: props.fieldKey,
            model_id: props.modelId
        }
        const { data } = await Image.show(id, payload)
        localImage.value = data
        emitImageSelected()
    } catch (error) {
        console.error(error)
    }
}

async function handleNewImage (item) {
    await pickImage(item.id)
}

function setImage (payload) {
    if (props.group === payload.group && instanceId !== payload.uid) {
        localImage.value = payload.data
    }
}

function emitImageSelected () {
    if (props.group) {
        this.eventBus.emit('image-selected', {
            group: group,
            data: { ...localImage.value },
            uid: instanceId
        })
    }
}

function showImageModal() {
    createModal(ImageModal, {
        id: localImage.value.id,
        onItemDeleted: () => {
            localImage.value = null
        },
    })
}

function showMediaPicker() {
    const modal = createModal(FMediaPicker, {
        mediaType: 'image',
        onItemPicked: (id) => {
            pickImage(id)
            modal.close()
        },
    })
}
</script>
