<template>
    <div class="space-y-1 w-full">
        <div class="flex justify-between items-baseline gap-5">
            <div class="flex gap-4 items-center">
                <div
                    class="rounded-full size-4 aspect-square"
                    :class="[
                        buttonColor ? buttonColor : 'border border-gray-300'
                    ]"
                />
                {{ localButton.text }}

                <ArrowPresenter :arrow="buttonArrow" />
            </div>

            <div class="flex gap-3">
                <button
                    type="button"
                    @click="showModal"
                >
                    <PenToSquareIcon class="size-5 text-gray-500 hover:text-gray-700" />
                </button>
                <button
                    type="button"
                    @click="$emit('delete')"
                    class=""
                >
                    <TrashIcon class="size-5 text-gray-500 hover:text-red-800" />
                </button>
            </div>

        </div>

        <div class="flex items-baseline text-sm text-gray-500">
            <div class="text-xs pr-3 font-semibold border-r border-gray-300 text-gray-400 leading-tight">{{ buttonType }}</div>
            <div class="pl-3">
                {{ buttonTarget}}
                <span
                    v-if="!buttonTarget"
                    class="text-italic text-xs"
                >
                    ej angivet
                </span>
            </div>
        </div>

        <ButtonModal
            :id="modalId"
            arrow
            color
            :value="value"
            @saved="onButtonSaved"
        />
    </div>
</template>

<script>
import PageTree from '@/models/PageTree.js'
import { colors } from '@/components/forms/FColorPicker.vue'
import { buttonArrows } from '@/components/forms/FArrowPicker.vue'
import PenToSquareIcon from '@/icons/PenToSquareIcon.vue'
import ArrowPresenter from '@/components/forms/ArrowPresenter.vue'
import ButtonModal from '@/components/modals/ButtonModal.vue'

export const linkTypeOptions = [
    { label: 'Intern', value: 'internal' },
    { label: 'Extern', value: 'external' },
    { label: 'Fil', value: 'file'}
]

export default {
    name: 'FButtonItem',
    components: { ArrowPresenter, ButtonModal },
    props: {
        pages: {
            type: Array,
            default: () => {
                return []
            }
        },
        value: {
            type: Object,
            default: () => {
                return {
                    text: '',
                    linkType: 'internal',
                    page_id: null,
                    file: {}
                }
            }
        },
        disabled: {
            type: Boolean,
            default: false
        },
        color: {
            type: Boolean,
            default: false
        },
        arrow: {
            type: Boolean,
            default: false
        },
        columnLayout: {
            type: Boolean,
            default: false
        },
        placeholder: {
            type: String,
            default: ''
        },
        canDelete: {
            type: Boolean,
            default: false
        }
    },
    data () {
        return {
            modalId: `button-modal-${Math.random().toString(20).substring(2,6)}`,
            localButton: this.value,
            localPages: this.pages,
            colors: colors['buttonColors'],
            arrows: buttonArrows,
            linkTypeOptions: linkTypeOptions,
        }
    },
    mounted () {
        if (this.pages.length === 0) {
            this.fetchPages()
        }
        setTimeout(() => {
            this.$emit('input', this.localButton)
        }, 10)
    },
    computed: {
        buttonType() {
            if(!this.localButton) return ''
            return this.linkTypeOptions.find(opt => opt.value === this.localButton.linkType)?.label ?? ''
        },
        buttonTarget() {
            if(!this.localButton) return ''

            return {
                'internal': this.localPages.find(page => page.id === this.localButton.page_id)?.name ?? '',
                'external': this.localButton.url ?? '',
                'file': this.localButton.file.file_name ?? '',
            }[this.localButton.linkType] ?? ''
        },
        buttonColor() {
            if(!this.localButton || !this.colors || this.localButton.linkType === 'file') return ''
            return this.colors.find(color => color.value === this.localButton.color)?.color ?? ''
        },
        buttonArrow() {
            if(!this.localButton || !this.arrows) return
            return this.arrows.find(arrow => arrow.value === this.localButton.arrow)
        },
    },
    methods: {
        async fetchPages () {
            try {
                const payload = {
                    params: {
                        // field: 'id,name',
                        append: 'paths',
                        selectOptions: true
                    }
                }
                const { data } = await PageTree.index(payload)
                this.localPages = data
            } catch (error) {
                console.error(error)
            }
        },

        checkForPages () {
            this.fetchPages()
        },

        showModal () {
            this.$vfm.show(this.modalId)
        },

        onButtonSaved(button) {
            this.localButton = button
            this.$emit('input', button)
        }
    }
}
</script>
