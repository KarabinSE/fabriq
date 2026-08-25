<template>
    <div>
        <FInput
            v-model="localContent.name"
            name="name"
            label="Namn"
            rules="required"
            class="max-w-120"
            help-text="Visas endast internt"
        />
        <hr class="w-full h-px my-6 text-gray-200">

        <div class="flex mb-10 gap-x-6 gap-y-6 flex-wrap">
            <FInput
                v-model="localContent.heading"
                name="header"
                class="basis-120"
                label="Rubriktext"
            />
        </div>
        <div
            class="flex gap-12 flex-1"
        >
            <FEditor
                v-model="localContent.body"
                label="Text"
            />

            <UiDashedBox
                class="basis-160 pt-3"
                size="min-h-full"
            >
                <template #header>
                    Autogenererat kontaktformulär
                </template>
            </UiDashedBox>
        </div>
    </div>
</template>
<script>
export default {
    name: 'ContactBlock',
    props: {
        index: {
            type: Number,
            default: 0
        },
        value: {
            type: [String, Number, Object, Array],
            default: () => {
                return {
                    name: '',
                    header: ''
                }
            }
        },
        content: {
            type: [Array, Object],
            required: true,
            default: () => {
                return {
                    name: '',
                    headerType: {
                        name: ''
                    }
                }
            }
        }
    },
    data () {
        return {
            localContent: this.content
        }
    },
    computed: {
        dragOptions () {
            return {
                animation: 200,
                group: 'description',
                disabled: false,
                ghostClass: 'ghost'
            }
        }
    },
    beforeMount () {
        this.$set(this.localContent, 'children', this.content.children ?? [])
    },
    methods: {
        deleteChild (index) {
            this.localContent.children.splice(index, 1)
        },
        copySuccess () {
            this.$toast.success({ title: 'Kortets ID har kopierats', message: 'Klistra in som en extern länk i fältet till kontrollen du önskar länka blocket till.' })
        }
    }
}
</script>
