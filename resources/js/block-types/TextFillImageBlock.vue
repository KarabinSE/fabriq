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
            <div class="flex flex-col gap-6 flex-1 basis-[30rem]">
                <FInput
                    v-model="localContent.heading"
                    name="header"
                    label="Rubriktext"
                    class="max-w-[30rem]"
                />
                <FEditor
                    v-model="localContent.body"
                    label="Text"
                />
            </div>
            <FMediaInput
                v-model="localContent.media"
                name="media"
                class="flex-1 basis-[20rem] max-w-md"
            />
        </div>

        <FButtonList
            v-model="localContent.buttons"
            color
            arrow
        >
            Knappar
        </FButtonList>
    </div>
</template>
<script>
export default {
    name: 'TextFillImageBlock',
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
        })
    }
}
</script>
