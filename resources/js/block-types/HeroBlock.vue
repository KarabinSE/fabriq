<template>
    <div class="flex flex-col gap-6">
        <div class="flex justify-between gap-12">
            <FInput
                v-model="localContent.name"
                name="name"
                class="basis-120"
                label="Namn"
                rules="required"
                help-text="Visas endast internt"
            />

            <FColorPicker
                v-model="localContent.bgColor"
                label="Bakgrundsfärg"
                collection="backgrounds"
            />
        </div>

        <hr class="w-full">

        <div class="flex gap-x-12 gap-y-6 flex-wrap">
            <div class="flex flex-col gap-6 flex-1 basis-120">
                <FInput
                    v-model="localContent.heading"
                    name="header"
                    label="Rubriktext"
                    class="max-w-120"
                />
                <FEditor
                    v-model="localContent.body"
                    label="Text"
                />
            </div>
            <div
                class="flex-1 basis-[20rem] max-w-md flex flex-col gap-y-3"
            >
                <div class="grid grid-cols-2 gap-x-5">
                    <FSelect
                        v-model="localContent.media.type"
                        label="Mediatyp"
                        name="mediaType"
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
                    <FSelect
                        v-model="localContent.media.fullscreen"
                        label="Helskärm"
                        name="mediaFullscreen"
                        :clearable="false"
                        :options="[
                            {
                                value: true,
                                label: 'Ja',
                            },
                            {
                                value: false,
                                label: 'Nej',
                            }
                        ]"
                    />
                </div>
                <FImageInput
                    v-if="localContent.media.type === 'image'"
                    v-model="localContent.media.image"
                    name="firstMediaImage"
                />
                <FVideoInput
                    v-if="localContent.media.type === 'video'"
                    v-model="localContent.media.video"
                    name="firstMediaVideo"
                />
            </div>
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
    name: 'HeroBlock',
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
    mounted () {
        this.$set(this.localContent, 'media', this.content?.media ?? {
            type: 'image',
            fullscreen: true,
            image: {},
            video: {}
        })
        this.$set(this.localContent, 'buttons', this.content?.buttons ?? [])
    }
}
</script>

