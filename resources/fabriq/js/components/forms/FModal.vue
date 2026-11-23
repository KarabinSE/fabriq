<template>
    <VueFinalModal
        v-slot="api"
        :lock-scroll="false"
        class="flex items-start justify-center sm:pt-12 pt-4"
        content-class="relative flex flex-col mx-4 bg-white rounded-lg shadow modal-content sm:mx-0"
        :click-to-close="clickToClose"
        :esc-to-close="escToClose"
        overlay-transition="vfm-fade"
        :content-transition="{
            enterActiveClass: 'duration-300',
            leaveActiveClass: 'duration-300',
            enterFromClass: 'opacity-0 scale-95',
            leaveToClass: 'opacity-0 scale-95',
            enterToClass: 'opacity-100 scale-100',
            leaveFromClass: 'opacity-100 scale-100',
        }"
        :modal-id="name"
    >
        <span class="py-6 border-b border-gray-200">
            <div class="flex items-center justify-between px-6">
                <span class="flex-1 text-xl font-light">
                    <slot
                        name="title"
                        v-bind="api"
                    />
                </span>
                <button
                    v-if="!hideCloseButton"
                    class
                    @click="api.close"
                >
                    <XMarkIcon
                        thin
                        class="block w-8 h-8 text-gray-800 "
                    />
                </button>
            </div>
        </span>
        <div
            class="relative z-0 w-screen"
            :class="[width, overflow]"
        >
            <div class="w-full px-6 py-3">
                <slot v-bind="api" />
                <div class="my-4">
                    <slot
                        class="modal__action"
                        name="actions"
                        v-bind="api"
                    />
                </div>
            </div>
        </div>
    </VueFinalModal>
</template>

<script setup>
import { VueFinalModal } from 'vue-final-modal'

defineOptions({
    name: 'FModal',
})
const props = defineProps ({
    name: {
        type: String,
        required: true,
    },
    width: {
        type: String,
        default: 'max-w-3xl',
    },
    overflow: {
        type: String,
        default: 'overflow-visible',
    },
    clickToClose: {
        type: Boolean,
        default: true,
    },
    escToClose: {
        type: Boolean,
        default: true,
    },
    hideCloseButton: {
        type: Boolean,
        default: false,
    },
})

</script>

<style scoped>
::v-deep .modal-content {
    max-height: 90% !important;
}
</style>
