<template>
    <div>
        <FLabel
            v-if="label"
            :padding="false"
            :name="name"
        >
            {{ label }}
        </FLabel>

        <div
            :ref="randomRef"
            class="relative flex items-center justify-center transition-colors duration-150 border-dashed rounded-md aspect-w-16 aspect-h-9 ring-inset ring-2 ring-royal-500"
            :class="[
                isDraggingOver ? 'bg-royal-100' : 'bg-royal-50'
            ]"
        >
            <div
                v-if="hasImage"
                class="absolute w-full h-full group"
            >
                <div class="absolute inset-0 z-10 flex items-end justify-center transition-opacity duration-300 opacity-0 group-hover:opacity-100 ">
                    <div class="flex w-full -mb-px">
                        <button
                            class="flex items-center justify-center w-full px-4 py-4 text-sm font-semibold leading-none text-white transition-colors duration-150 bg-gray-800 focus:outline-none rounded-bl-md hover:bg-gray-900"
                            @click.prevent="selectImage"
                        >
                            Byt
                        </button>

                        <button
                            class="flex items-center justify-center w-full px-4 py-4 text-sm font-semibold leading-none text-white transition-colors duration-150 bg-gray-800 focus:outline-none rounded-br-md hover:bg-gray-900"
                            @click.prevent="clearImage"
                        >
                            Ta bort
                        </button>
                    </div>
                </div>

                <div class="absolute inset-0 transition-opacity duration-300 bg-black rounded-md opacity-0 group-hover:opacity-50 overlay " />

                <img
                    :src="selectedFilePreview"
                    class="block object-cover w-full h-full rounded-md"
                >
            </div>

            <div
                v-else
                class="absolute flex flex-col items-center justify-center"
            >
                <div
                    class="flex flex-col items-center space-y-2 text-sm "
                >
                    <button
                        type="button"
                        class="inline-flex px-6 py-2.5 mb-2 text-sm leading-none fabriq-btn btn-royal"
                        @click="selectImage"
                    >
                        Ladda upp bild
                    </button>
                </div>
            </div>

            <input
                ref="fileInput"
                type="file"
                class="hidden"
                accept="image/*"
                @input="(e) => onImageSelected(e.target.files[0])"
            >
        </div>
    </div>
</template>
<script>
export default {
    name: 'FImageInput',
    props: {
        value: {
            type: [File],
            default: null
        },
        label: {
            type: String,
            default: ''
        },
        name: {
            type: String,
            default: ''
        },
        previewSrc: {
            type: String,
            default: null
        }
    },
    data () {
        return {
            isDraggingOver: false,
            selectedFilePreview: null,
        }
    },
    computed: {
        randomRef () {
            return Math.random().toString(20).substr(2, 6)
        },
        hasImage () {
            return this.selectedFilePreview
        },
    },
    mounted () {
        this.selectedFilePreview = this.previewSrc
        // this.$refs[this.randomRef].addEventListener('dragover', this.handleDragOver)
        // this.$refs[this.randomRef].addEventListener('dragleave', this.handleDragLeave)
    },
    beforeDestroy () {
        // this.$refs[this.randomRef].removeEventListener('dragover', this.handleDragOver)
        // this.$refs[this.randomRef].removeEventListener('dragleave', this.handleDragLeave)

        if(this.selectedFilePreview) {
            window.URL.revokeObjectURL(this.selectedFilePreview)
        }
    },
    methods: {
        selectImage() {
            this.$refs['fileInput']?.click()
        },
        handleDragOver () {
            this.isDraggingOver = true
        },
        handleDragLeave () {
            this.isDraggingOver = false
        },
        clearImage () {
            this.isDraggingOver = false

            window.URL.revokeObjectURL(this.selectedFilePreview)
            this.selectedFilePreview = null
            this.$emit('input', null)
        },
        onImageSelected(file) {
            if(this.selectedFilePreview) {
                window.URL.revokeObjectURL(this.selectedFilePreview)
            }
            this.selectedFilePreview = window.URL.createObjectURL(file)
            this.$emit('input', file)
        },
    }
}
</script>
