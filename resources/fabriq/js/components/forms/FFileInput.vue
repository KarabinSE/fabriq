<template>
    <div>
        <FLabel
            v-if="label"
            :name="name"
            :padding="false"
            :required="hasRuleRequired"
        >
            {{ label }}
        </FLabel>
        <div class="flex items-center space-x-4 min-h-10">
            <button
                class="px-4 py-2 text-xs cursor-pointer link fabriq-button btn-gold whitespace-nowrap"
                type="button"
                @click="pickerOpen = true"
            >
                Välj fil
            </button>

            <div
                v-if="hasFile"
                class="flex items-center space-x-4 text-sm overflow-hidden w-full"
            >
                <div class="text-xs truncate">
                    {{ localFile.file_name }}
                </div>
                <div class="flex justify-start space-x-2 ml-auto">
                    <button
                        class="font-semibold text-left focus:outline-none"
                        @click="vfm.open('file-modal', {id: localFile.id})"
                    >
                        <PenToSquareIcon class="w-4 h-4" />
                    </button>
                    <button
                        class="font-semibold focus:outline-none"
                        @click="clearFile"
                    >
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>
            </div>

            <div v-else>
                <span class="text-xs italic text-neutral-400">Ingen fil vald</span>
            </div>
        </div>
    </div>
</template>

<script>
import File from '@fabriq/models/File.js'
import { useVfm } from 'vue-final-modal'
import { useEventBus } from '@fabriq/services/eventBus'
import FMediaPicker from '@fabriq/components/forms/FMediaPicker.vue'

export default {
    name: 'FFileInput',

    props: {
        value: {
            type: [String, Number, Object, Array],
            default: null,
        },

        label: {
            type: String,
            default: '',
        },

        modelId: {
            type: Number,
            required: false,
            default: 0,
        },

        fieldKey: {
            type: String,
            required: false,
            default: '',
        },

        multiple: {
            type: Boolean,
            default: false,
        },

        modelName: {
            type: String,
            default: '',
        },

        buttonText: {
            type: String,
            default: '',
        },

        group: {
            type: String,
            default: '',
        },

        syncSimilar: {
            type: Boolean,
            default: false,
        },

        name: {
            type: String,
            default: '',
        },

        validationMode: {
            type: String,
            default: 'eager',
        },

        rules: {
            type: String,
            default: '',
        },

        absolutePosErrors: {
            type: Boolean,
            default: false,
        },

        hideErrors: {
            type: Boolean,
            default: false,
        },

    },

    setup() {
        const eventBus = useEventBus()

        return {
            eventBus, 
        }
    },

    setup() {
        const vfm = useVfm()

        return {
            vfm, 
        }
    },

    data () {
        return {
            pickerOpen: false,
            localFile: null,
            isDraggingOver: false,
        }
    },

    computed: {
        randomRef () {
            return Math.random().toString(20).substr(2, 6)
        },

        randomButtonRef () {
            return Math.random().toString(20).substr(2, 6)
        },

        hasFile () {
            return this.localFile.id
        },

        hasRuleRequired () {
            return this.rules.includes('required')
        },

    },

    created () {
        this.localFile = {
            ...this.value, 
        }
        this.$emit('input', this.localFile)
    },

    mounted () {
        // add the needed event listeners to the container

        // Listen on other group events
        if (this.group) {
            this.eventBus.on('file-selected', this.setFile)
        }
    },

    methods: {
        clearFile () {
            this.$emit('input', null)
            this.localFile = {
            }
            this.isDraggingOver = false
            this.emitFileSelected()
        },

        pickFile (id) {
            if (!this.multiple) {
                this.pickSingleFile(id)
            }

            if (this.multiple) {
                this.pickMultipleFiles(id)
            }

            this.isDraggingOver = false
        },

        async pickMultipleFiles (id) {
            try {
                const payload = {
                    model_id: this.modelId,
                }
                const { data } = await File.attachToModel(id, this.modelName, payload)

                this.$emit('file-picked', data)
                this.pickerOpen = false
            } catch (error) {
                console.error(error)
            }
        },

        async pickSingleFile (id) {
            try {
                const payload = {
                    key: this.fieldKey,
                    model_id: this.modelId,
                }
                const { data } = await File.show(id, payload)

                this.localFile = data
                this.$emit('input', data)
                this.emitFileSelected()
                this.pickerOpen = false
            } catch (error) {
                console.error(error)
            }
        },

        async handleNewFile (item) {
            await this.pickFile(item.id)
        },

        setFile (payload) {
            if (this.group === payload.group && this.randomRef !== payload.randomRef) {
                this.localFile = payload.data
                this.$emit('input', payload.data)
            }
        },

        emitFileSelected () {
            // If a group is specified we want to sync the input with
            // all other inputs in the same group. Useful when having multiple
            // inputs for a bunch of langugages and the input is not translated
            if (this.group) {
                this.eventBus.emit('file-selected', {
                    group: this.group,
                    data: {
                        ...this.localFile, 
                    },
                    randomRef: this.randomRef,
                })
            }
        },

        convertErrorMessage (string) {
            if (string.includes('{field}')) {
                return string.replace('{field}', this.label.toLowerCase())
            }

            return string
        },

        showMediaPicker() {
            const modal = createModal(FMediaPicker, {
                mediaType: 'file',
                onItemPicked: (id) => {
                    this.pickFile(id)
                    modal.close()
                },
            })
        },
    },
}
</script>
