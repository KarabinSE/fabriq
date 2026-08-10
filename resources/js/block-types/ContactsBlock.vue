<template>
    <div>
        <div class="grid grid-cols-3 mb-6 gap-x-6">
            <FInput
                v-model="localContent.name"
                name="name"
                label="Namn"
                rules="required"
                help-text="Visas endast internt"
            />
        </div>
        <div class="grid grid-cols-12 mb-10 gap-x-6 gap-y-6">
            <FInput
                v-model="localContent.heading"
                name="header"
                class="col-span-4"
                label="Rubriktext"
            />
            <FEditor
                v-model="localContent.body"
                name="body"
                class="col-span-12 row-start-2"
                label="Text"
            />
        </div>
        <div
            class="col-span-12"
            size="min-h-24"
        >
            <UiDashedBox>
                <template #header>
                    Listning av kontakter
                </template>
                <template #link>
                    <RouterLink :to="{name: 'contacts.index'}">
                        Gå till kontakter
                    </RouterLink>
                </template>
            </UiDashedBox>
        </div>
    </div>
</template>
<script>
export default {
    name: 'ContactsBlock',
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
                    // repeaters: []
                }
            }
        }
    },
    data () {
        return {
            headerTypes: [
                {
                    text: 'Heading 1',
                    value: 'h1'
                },
                {
                    text: 'Heading 2',
                    value: 'h2'
                },
                {
                    text: 'Heading 3',
                    value: 'h3'
                }
            ],
            // repeaters: this.content.repeaters,
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
    mounted () {
        this.$set(this.localContent, 'button', { text: '', linkType: 'internal', page_id: null })
    },
}
</script>
