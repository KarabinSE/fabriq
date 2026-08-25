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
        </div>

        <hr class="w-full">

        <FChildren
            v-slot="{child}"
            v-model="localContent.children"
            can-clone
            can-copy
            can-hide
            :max="3"
            :labels="{
                title: 'Höjdpunkter',
                add: 'Lägg till höjdpunkt',
                empty: 'Inga höjdpunkt har lagts till ännu',
            }"
            :item-content="{
                media: {
                    type: 'image',
                    image: null,
                    video: null,
                }
            }"
        >
            <div class="flex gap-x-12 gap-y-6 flex-wrap">
                <div class="flex flex-col gap-6 flex-1 basis-120">
                    <FInput
                        v-model="child.heading"
                        name="header"
                        label="Rubriktext"
                        class="max-w-120"
                    />
                    <FEditor
                        v-model="child.body"
                        label="Text"
                    />
                </div>
                <FImageInput
                    v-model="child.media.image"
                    label="Bild/ikon"
                    name="media"
                    class="flex-1 basis-[20rem] max-w-md"
                />
            </div>
        </FChildren>
    </div>
</template>
<script>
export default {
    name: 'HighlightsBlock',
    props: {
        content: {
            type: [Array, Object],
            required: true,
            default: () => {
                return {
                    name: '',
                }
            }
        }
    },
    data () {
        return {
            localContent: this.content
        }
    },
}
</script>
