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
            <div class="flex flex-1 basis-[20rem] max-w-md gap-5">
                <FSelect
                    v-model="localContent.bgType"
                    label="Bakgrundstyp"
                    name="backgroundType"
                    v-bind="$attrs"
                    class="flex-1"
                    :clearable="false"
                    :options="[
                        {
                            value: 'card',
                            label: 'Kort',
                        },
                        {
                            value: 'fullscreen',
                            label: 'Fyll',
                        }
                    ]"
                />
                <FColorPicker
                    v-model="localContent.bgColor"
                    label="Bakgrundsfärg"
                    class="flex-1"
                    collection="backgrounds"
                />
            </div>
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
            <FMediaInput
                v-model="localContent.media"
                name="media"
                class="flex-1 basis-[20rem] max-w-md"
            >
                <FSelect
                    v-model="localContent.media.placement"
                    :label="`Vilken sida ska ${localContent.media.type === 'image' ? 'bilden' : 'videon'} vara på?`"
                    name="mediaPlacement"
                    class="grow"
                    v-bind="$attrs"
                    :clearable="false"
                    :options="[
                        {
                            value: 'left',
                            label: 'Vänster',
                        },
                        {
                            value: 'right',
                            label: 'Höger',
                        }
                    ]"
                />
            </FMediaInput>
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
import FColorPicker from '@/components/forms/FColorPicker.vue';

export default {
    name: 'TextImageBlock',
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
        this.$set(this.localContent, 'bgColor', this.content.bgColor ?? 'primary')
        this.$set(this.localContent, 'bgType', this.content.bgType ?? 'fullscreen')
    }
}
</script>
