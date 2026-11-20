<template>
    <div class="flex justify-center w-full">
        <Transition
            v-if="!withoutLoader"
            name="fade"
        >
            <div
                v-show="isUploading"
                class="flex items-center text-sm"
            >
                <div>
                    Laddar upp {{ numUploads }} filer

                    <div
                        v-show="progress === 100"
                        class="absolute text-xs processing-text font-semibold"
                    >
                        Bearbetar filer
                    </div>
                </div>

                <span class="inline-block w-12 ml-1">
                    ({{ progressPercentage }}%)
                </span>

                <span class="inline-flex mx-4 animate-spin">
                    <SpinIcon class="size-5 text-royal-500" />
                </span>
                {{ progress }}
            </div>
        </Transition>

        <div v-if="!withoutButton">
            <slot name="button">
                <button
                    type="button"
                    class="px-6 leading-none py-2.5 text-sm font-semibold fabriq-btn btn-royal"
                    @click="openFileDialog()"
                >
                    Ladda upp
                </button>
            </slot>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useDropZone, useFileDialog } from '@vueuse/core'
import axios from 'axios'
import Cookies from 'js-cookie'

defineOptions({ name: 'FUpload' })

const props = defineProps({
    modelValue: {
        type: [String, Number, Object, Array],
        default: ''
    },
    dropArea: {
        type: Boolean,
        default: false
    },
    maxItems: {
        type: Number,
        default: 999
    },
    uploadName: {
        type: String,
        default: 'file'
    },
    endpoint: {
        type: String,
        required: true
    },
    types: {
        type: String,
        default: ''
    },
    payload: {
        type: Object,
        default: () => ({})
    },
    withoutButton: {
        type: Boolean,
        default: false
    },
    withoutLoader: {
        type: Boolean,
        default: false
    },
    dropRef: {
        type: Object,
        default: null
    },
    buttonRef: {
        type: Object,
        default: null
    },
})

const emit = defineEmits([
    'update:model-value',
    'input',
    'added-file',
    'upload-complete',
    'upload-queue-complete',
    'error',
    'is-over-dropzone'
])

const uploadButton = computed(() => props.buttonRef)
const dropElement = computed(() => props.dropRef || document.body)

const numUploads = ref(0)
const isUploading = ref(false)
const progress = ref(0)

const progressPercentage = computed(() => {
    return progress.value === 100 ? 100 : progress.value.toFixed(0)
})

// File dialog
const { open: openFileDialog, onChange } = useFileDialog({
    multiple: true,
    accept: props.types || undefined,
})

// File picker
onChange((files) => {
    if (files?.length) {
        uploadFiles(files)
    }
})

// Validate file types
const isValidFileType = (file) => {
    if (!props.types) return true

    const acceptedTypes = props.types
        .split(',')
        .map(type => type.trim().toLowerCase())

    return acceptedTypes.some(type => {
        if (type.startsWith('.')) {
            return file.name.toLowerCase().endsWith(type)
        }

        if (type.endsWith('/*')) {
            return file.type.startsWith(type.replace('/*', '/'))
        }

        return file.type.toLowerCase() === type
    })
}

// Calculate total upload progress
const updateProgress = (files) => {
    const total = files.reduce((sum, file) => sum + file.size, 0)
    const uploaded = files.reduce((sum, file) => sum + (file.uploaded || 0), 0)

    progress.value = total? Math.min((uploaded / total) * 100, 100): 0
}

// Upload file with Axios
const uploadFile = async (file, files) => {
    const formData = new FormData()

    formData.append(props.uploadName, file)

    Object.entries(props.payload).forEach(([key, value]) => {
        if (value === null || value === undefined) return

        if (Array.isArray(value)) {
            value.forEach(item => {
                formData.append(`${key}[]`, item)
            })
        } else if (typeof value === 'object') {
            formData.append(key, JSON.stringify(value))
        } else {
            formData.append(key, String(value))
        }
    })

    try {
        const token = Cookies.get('XSRF-TOKEN')
        const config =  {
            withCredentials: true,
            headers: {
                ...(token && {
                    'X-XSRF-TOKEN': token
                })
            },
            onUploadProgress: (event) => {
                file.uploaded = event.loaded
                updateProgress(files)
            }
        }

        const { data } = await axios.post(props.endpoint, formData, config)

        file.uploaded = file.size
        updateProgress(files)

        emit('upload-complete', data)
        emit('update:model-value', data)
        emit('input', data)

        return data
    } catch (error) {
        const message = error.response?.data?.message || error.message || 'Upload failed'

        console.error(file, error)

        emit('error', error)

        return null
    }
}

// Upload queue
let isProcessing = false

const uploadFiles = async (files) => {
    if (!files?.length) {
        return
    }

    const validFiles = [...files]
        .filter(isValidFileType)
        .slice(0, Math.max(0, props.maxItems - numUploads.value))

    if (validFiles.length === 0) {
        return
    }

    numUploads.value += validFiles.length
    isUploading.value = true

    validFiles.forEach(file => {
        file.uploaded = 0
        emit('added-file')
    })

    if (isProcessing) {
        return
    }

    isProcessing = true

    const queue = [...validFiles]

    const worker = async () => {
        while (queue.length > 0) {
            const file = queue.shift()
            await uploadFile(file, validFiles)
        }
    }

    await Promise.all(
        Array.from(
            { length: Math.min(5, queue.length) },
            () => worker()
        )
    )

    isProcessing = false
    progress.value = 100

    setTimeout(() => {
        numUploads.value = 0
        progress.value = 0
        isUploading.value = false

        emit('upload-queue-complete')
    }, 600)
}

// Dropzone
const { isOverDropZone } = useDropZone(dropElement, {
    multiple: true,
    onDrop: uploadFiles
})

watch(isOverDropZone, () => emit('is-over-dropzone', isOverDropZone.value))

// Clipboard paste
const handlePaste = (event) => {
    const items = event.clipboardData?.items || []

    const files = [...items]
        .filter(item => item.kind === 'file')
        .map(item => item.getAsFile())
        .filter(Boolean)

    uploadFiles(files)
}


watch(uploadButton, (btn, oldBtn) => {
    oldBtn?.removeEventListener('click', openFileDialog)
    btn.addEventListener('click', openFileDialog)
})

onMounted(() => {
    document.addEventListener('paste', handlePaste)
})

onUnmounted(() => {
    document.removeEventListener('paste', handlePaste)
})
</script>
