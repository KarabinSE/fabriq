<template>
    <div>
        <div class=" flex flex-col gap-y-3">
            <div class="flex gap-5 justify-between">
                <FSelect
                    v-model="localValue.type"
                    label="Mediatyp"
                    class="grow"
                    :name="name"
                    v-bind="$attrs"
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
                v-if="localValue.type === 'image'"
                v-model="localValue.image"
                name="firstMediaImage"
            />
            <FVideoInput
                v-if="localValue.type === 'video'"
                v-model="localValue.video"
                name="firstMediaVideo"
            />
        </div>
    </div>
</template>

<script>
export default {
    name: 'FMediaInput',
    props: {
        name: {
            type: String,
            required: true,
        },
        value: {
            type: Object,
            default: () => ({
                type: 'image',
                image: null,
                video: null,
            }),
        },
    },

    emits: ['input'],

    data() {
        return {
            localValue: { ...this.value },
        }
    },

    watch: {
        // value: {
        //     handler(newValue) {
        //         this.localValue = { ...newValue }
        //     },
        //     deep: true,
        // },
        localValue: {
            handler(newValue) {
                this.$emit('input', newValue)
            },
            deep: true,
        },
    },

    methods: {
        emitValue(value) {
            this.$emit('input', value)
        },
    }
}
</script>
