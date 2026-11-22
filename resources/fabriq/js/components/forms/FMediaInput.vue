<template>
    <div>
        <div class=" flex flex-col gap-y-3">
            <div class="flex gap-5 justify-between">
                <FSelect
                    v-model="media.type"
                    label="Mediatyp"
                    class="flex-1"
                    :name="name"
                    :clearable="false"
                    :options="[
                        {
                            value: 'image',
                            label: 'Bild',
                        },
                        {
                            value: 'video',
                            label: 'Video',
                        }
                    ]"
                />
                <slot />
            </div>
            <FImageInput
                v-if="media.type === 'image'"
                v-model="media.image"
                name="firstMediaImage"
            />
            <FVideoInput
                v-if="media.type === 'video'"
                v-model="media.video"
                name="firstMediaVideo"
            />
        </div>
    </div>
</template>

<script setup>
import { Media } from '@fabriq/models/Media'
import { watchEffect } from 'vue'

defineOptions({
    name: 'FMediaInput', 
})
const emits = defineEmits(['update:model-value'])
const  props = defineProps({
    name: {
        type: String,
        required: true,
    },
})

const media = defineModel({
    type: Object,
    default: () => new Media(),
})

watchEffect(() => {
    if(media.value === undefined) {
        emits('update:model-value', new Media)
    }
})
</script>
