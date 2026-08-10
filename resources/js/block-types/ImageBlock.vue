<template>
    <div class="flex flex-col gap-6">
        <div class="flex justify-between gap-12">
            <FInput
                v-model="localContent.name"
                name="name"
                class="basis-[30rem]"
                label="Namn"
                rules="required"
                help-text="Visas endast internt"
            />
        </div>

        <hr class="w-full">

        <div class="flex gap-x-12 gap-y-6 flex-wrap">
            <FMediaInput
                v-model="localContent.media"
                name="media"
                class="flex-1 basis-[20rem] max-w-md"
            />
        </div>
    </div>
</template>
<script>
export default {
    name: 'ImageBlock',
    props: {
        content: {
            type: [Array, Object],
            required: true,
            default: () => ({
                name: '',
            })
        }
    },
    data () {
        return {
            localContent: this.content,
        }
    },
    beforeMount () {
        this.$set(this.localContent, 'media', this.content.media ?? {
            type: 'image',
            image: null,
            video: null,
            placement: 'right'
        })
    }
}
</script>
